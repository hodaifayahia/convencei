<?php

namespace App\Http\Controllers;

use App\Models\FicheNavette;
use App\Models\FicheNavetteItem;
use App\Models\Convention; // Assuming your conventions are stored as Convention model
use App\Models\Patient; // Assuming your conventions are stored as Convention model
use App\Models\Company; // Assuming your conventions are stored as Convention model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf; // Import the PDF facade
use Carbon\Carbon; // Import Carbon for date formatting
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule; // Import Rule for conditional validation

class FicheNavetteController extends Controller
{

     /**
     * Display a listing of the Fiche Navettes.
     */
   public function index(Request $request)
    {
        // Get all filter parameters from the request
        $filters = $request->only(['search', 'status', 'start_date', 'end_date', 'company_id', 'patient_id']);

        // Start building the FicheNavette query with eager loading
        $ficheNavettesQuery = FicheNavette::with(['patient', 'items.convention', 'items.convention.company']);

        // --- Apply the complex 'search' filter (OR conditions within this block) ---
        if ($filters['search'] ?? null) {
            $search = $filters['search'];
            $ficheNavettesQuery->where(function ($query) use ($search) {
                // 1. Search directly on FicheNavette fields
                $query->where('FNnumber', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('fiche_date', 'like', '%' . $search . '%');

                // 2. Search on Patient model and get matching patient IDs
                $matchingPatientIds = Patient::where(function ($q) use ($search) {
                    $q->where('Firstname', 'like', '%' . $search . '%')
                        ->orWhere('Lastname', 'like', '%' . $search . '%')
                        ->orWhere('Parent', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('Idnum', 'like', '%' . $search . '%')
                        ->orWhere('nss', 'like', '%' . $search . '%')
                        ->orWhere('dateOfBirth', 'like', '%' . $search . '%');
                })->pluck('id')->toArray();

                // Add a condition to the FicheNavette query if there are matching patient IDs
                if (!empty($matchingPatientIds)) {
                    $query->orWhereIn('patient_id', $matchingPatientIds);
                }

                // 3. Search on beneficiary fields (if not 'adherent')
                $query->orWhere(function ($q) use ($search) {
                    $q->where('family_auth', '!=', 'adherent')
                        ->where(function ($qq) use ($search) {
                            $qq->where('first_name_beneficiary', 'like', '%' . $search . '%')
                                ->orWhere('last_name_beneficiary', 'like', '%' . $search . '%')
                                ->orWhere('phone_beneficiary', 'like', '%' . $search . '%');
                        });
                });

                // 4. Search on Company name
                $query->orWhereHas('items.convention.company', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        // --- Apply specific filters (AND conditions) ---

        // Filter by Status
        $ficheNavettesQuery->when($filters['status'] ?? null, function ($query, $status) {
            if ($status !== 'All') { // Assuming 'All' is a special value to show all statuses
                $query->where('status', $status);
            }
        });

        // Filter by Start Date
        $ficheNavettesQuery->when($filters['start_date'] ?? null, function ($query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        });

        // Filter by End Date
        $ficheNavettesQuery->when($filters['end_date'] ?? null, function ($query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });

        // Filter by Company ID
        $ficheNavettesQuery->when($filters['company_id'] ?? null, function ($query, $companyId) {
            $query->whereHas('items.convention.company', function ($q) use ($companyId) {
                $q->where('id', $companyId);
            });
        });

        // Filter by Patient ID
        $ficheNavettesQuery->when($filters['patient_id'] ?? null, function ($query, $patientId) {
            $query->where('patient_id', $patientId);
        });

        // Execute the query, order, and paginate
        $ficheNavettes = $ficheNavettesQuery->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString(); // Keep all filter parameters in pagination links

        // Fetch all patients and companies to populate dropdowns on the frontend
        $allPatients = Patient::orderBy('Lastname')->get(['id', 'Firstname', 'Lastname', 'Idnum']);
        $allCompanies = Company::orderBy('name')->get(['id', 'name']);

        // Get the next FNnumber for the modal to display
        $nextFNnumber = $this->getNextFNnumber();

        return Inertia::render('FicheNavettes/Index', [
            'ficheNavettes' => $ficheNavettes,
            'allPatients' => $allPatients,
            'allCompanies' => $allCompanies, // Pass all companies for the dropdown
            'filters' => $filters, // Pass all current filters back to the frontend
            'flash' => session('flash'), // Assuming you still need flash messages
            'nextFNnumber' => $nextFNnumber, // Pass the next FNnumber to the frontend
        ]);
    }
        // Helper method to generate the next FNnumber
    private function getNextFNnumber(): string
    {
        $currentYear = Carbon::now()->year;

        // Find the latest FNnumber for the current year
        $lastFicheNavette = FicheNavette::where('FNnumber', 'like', "%/{$currentYear}")
                                        ->orderBy(DB::raw("CAST(SUBSTRING_INDEX(FNnumber, '/', 1) AS UNSIGNED)"), 'desc')
                                        ->first();

        $nextNumber = 1;
        if ($lastFicheNavette) {
            $parts = explode('/', $lastFicheNavette->FNnumber);
            if (count($parts) === 2 && (int)$parts[1] === $currentYear) {
                $nextNumber = (int)$parts[0] + 1;
            }
        }
        return "{$nextNumber}/{$currentYear}";
    }
     public function updateStatus(Request $request, FicheNavette $ficheNavette)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected,completed,cancelled',
        ]);

        $ficheNavette->update(['status' => $validated['status']]);

        return Redirect::back()->with('success', 'Fiche Navette status updated successfully!');
    }

       public function indexForPatient(Request $request, Patient $patient)
    {
        // Get search query for filtering within this patient's Fiche Navettes
        $search = $request->input('search');

        // *** CRUCIAL CHANGE HERE: Directly query FicheNavette and filter by patient_id ***
        // This avoids cross-database join issues with the relationship method.
        $ficheNavettesQuery = FicheNavette::where('patient_id', $patient->id)
                                         ->with(['patient', 'items.convention', 'items.convention.company']);

        if ($search) {
            $ficheNavettesQuery->where(function ($query) use ($search) {
                $query->where('FNnumber', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%')
                      ->orWhere('fiche_date', 'like', '%' . $search . '%')
                      // Search beneficiary fields if not 'adherent' for this specific fiche navette
                      ->orWhere(function ($q) use ($search) {
                          $q->where('family_auth', '!=', 'adherent')
                            ->where(function ($qq) use ($search) {
                                $qq->where('first_name_beneficiary', 'like', '%' . $search . '%')
                                   ->orWhere('last_name_beneficiary', 'like', '%' . $search . '%')
                                   ->orWhere('phone_beneficiary', 'like', '%' . $search . '%');
                            });
                      })
                      // Add search for convention designation or company name here
                      // These relationships (items.convention, items.convention.company) are within
                      // the same default database connection as FicheNavette, so orWhereHas should work.
                      ->orWhereHas('items.convention', function($q) use ($search) {
                          $q->where('designation_prestation', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('items.convention.company', function($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%');
                      });
            });
        }

        $ficheNavettes = $ficheNavettesQuery->orderBy('created_at', 'desc')
                                           ->paginate(10)
                                           ->withQueryString();

        return Inertia::render('Patients/ShowFicheNavettes', [
            'patient' => $patient, // Pass the patient object
            'ficheNavettes' => $ficheNavettes, // Pass the paginated Fiche Navettes for this patient
            'filters' => $request->only('search'),
        ]);
    }
      // This is the new method that will be called after successful creation
    // It combines showing the details and triggering print
    public function showAndPrint(FicheNavette $ficheNavette)
    {
        // Load any relationships needed for displaying details or for the PDF
        $ficheNavette->load('patient', 'items.convention');

        // This Inertia response is for showing the details page
        return Inertia::render('FicheNavette/Show', [ // Assuming you have a Show page for Fiche Navette
            'ficheNavette' => $ficheNavette,
            'triggerPrint' => true, // Flag to tell the frontend to print
            'printUrl' => route('fichesnavette.ticket-pdf', $ficheNavette->id), // Pass the print URL
        ]);
    }

    public function generateTicketPdf(FicheNavette $ficheNavette)
    {
        // Ensure relationships are loaded if they are not already
        $ficheNavette->load('patient', 'items.convention');

        $data = [
            'ficheNavette' => $ficheNavette,
            'currentDateTime' => Carbon::now()->locale('fr_FR')->isoFormat('DD/MM/YYYY HH:mm'),
            'userName' => auth()->user()->name ?? 'Utilisateur Inconnu', // Get actual user if authenticated
        ];

        $pdf = Pdf::loadView('tickets.fiche_navette_print', $data);

        // Stream the PDF directly to the browser (will typically open in new tab/download)
        return $pdf->stream('fiche_navette_' . $ficheNavette->FNnumber . '.pdf');
    }
    /**
     * Store a newly created Fiche Navette in storage.
     */
  public function store(Request $request)
{
    // Log the incoming request data for debugging
    Log::info('FicheNavette store request data:', $request->all());

    // Define validation rules
    $rules = [
        'patient_id' => 'required', // patient_id should be required and exist
        'convention_ids' => 'required|array|min:1', // Ensure at least one convention is selected
        'convention_ids.*' => 'integer|exists:convention,id', // Validate each ID against the 'conventions' table
        'fiche_date' => 'required|date', // Ensure fiche_date is validated and required
        'FNnumber' => [
            'nullable',
            'string',
            'max:255',
        ],
        'family_auth' => 'required|string|in:ascendant,descendant,conjoint,adherent,autre', // Required and must be one of the specified values

        // Beneficiary fields are required UNLESS family_auth is 'adherent'
        'first_name_beneficiary' => [
            Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
            'nullable', // Use nullable because required_if handles the presence
            'string',
            'max:255',
        ],
        'last_name_beneficiary' => [
            Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
            'nullable',
            'string',
            'max:255',
        ],
        'phone_beneficiary' => [
            Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
            'nullable',
            'string',
            'max:20',
        ],
        'email_beneficiary' => 'nullable|email|max:255',
        'address_beneficiary' => 'nullable|string|max:255',
        'prise_en_charge_number' => 'nullable|string|max:255',
        'number_mutuelle' => 'nullable|string|max:255',

        // Price fields (calculated on frontend, but re-validated/re-calculated on backend for security)
        'base_price' => 'nullable|numeric|min:0',
        'final_price' => 'nullable|numeric|min:0',
        'patient_share' => 'nullable|numeric|min:0',
        'organisme_share' => 'nullable|numeric|min:0',
        'status' => 'nullable|string|max:255', // e.g., pending, approved, rejected
    ];

    // Perform validation
    $validated = $request->validate($rules);

    // Fetch the actual Convention models based on the validated IDs
    // This is crucial for re-calculating totals securely on the backend.
    $selectedConventions = Convention::whereIn('id', $validated['convention_ids'])->get();

    // Re-calculate totals on the backend for security and data integrity
    $totalMontantHt = 0;
    $totalMontantGlobalTtc = 0;
    $totalMontantPriseChargeEntreprise = 0;
    $totalMontantPriseChargeBeneficiaire = 0;

    foreach ($selectedConventions as $convention) {
        $totalMontantHt += $convention->montant_ht;
        $totalMontantGlobalTtc += $convention->montant_global_ttc;
        $totalMontantPriseChargeEntreprise += $convention->montant_prise_charge_entreprise;
        $totalMontantPriseChargeBeneficiaire += $convention->montant_prise_charge_beneficiaire;
    }

    // Use the backend calculated values
    $validated['base_price'] = $totalMontantHt;
    $validated['final_price'] = $totalMontantGlobalTtc;
    $validated['organisme_share'] = $totalMontantPriseChargeEntreprise;
    $validated['patient_share'] = $totalMontantPriseChargeBeneficiaire;

    $ficheNavette = null; // Declare outside transaction to be accessible later

    DB::transaction(function () use ($validated, $selectedConventions, &$ficheNavette) {
        $ficheNavette = FicheNavette::create([
            'patient_id' => $validated['patient_id'],
            'fiche_date' => $validated['fiche_date'],
            'family_auth' => $validated['family_auth'],
            'FNnumber' => $validated['FNnumber'] ?? null, // Use null coalescing to handle optional FNnumber
            'base_price' => $validated['base_price'],
            'final_price' => $validated['final_price'],
            'patient_share' => $validated['patient_share'],
            'organisme_share' => $validated['organisme_share'],
            'status' => $validated['status'] ?? 'pending', // Default status if not provided

            // Include beneficiary fields conditionally, using null coalescing for safety
            'first_name_beneficiary' => $validated['first_name_beneficiary'] ?? null,
            'last_name_beneficiary' => $validated['last_name_beneficiary'] ?? null,
            'phone_beneficiary' => $validated['phone_beneficiary'] ?? null,
            'email_beneficiary' => $validated['email_beneficiary'] ?? null,
            'address_beneficiary' => $validated['address_beneficiary'] ?? null,
            'prise_en_charge_number' => $validated['prise_en_charge_number'] ?? null,
            'number_mutuelle' => $validated['number_mutuelle'] ?? null,
        ]);

        // Attach selected conventions as FicheNavetteItems
        foreach ($selectedConventions as $convention) {
            FicheNavetteItem::create([
                'fiche_navette_id' => $ficheNavette->id,
                'prestation_id' => $convention->id,
                // If you have other pivot data like quantity, price_at_time_of_creation, etc., add them here
                // e.g., 'quantity' => 1,
                // 'price' => $convention->montant_global_ttc,
            ]);
        }
    });

    
    // FIXED: Return the data in the format expected by Inertia forms
    // This will be available in the onSuccess callback's page parameter
     return redirect()->route('fichesnavette.index')
        ->with('success', 'Fiche Navette created successfully!')
        ->with('ficheNavette', $ficheNavette); // Pass the created fiche navette for further actions

}

    /**
     * Show the form for editing the specified Fiche Navette.
     */
     public function edit(FicheNavette $ficheNavette)
    {
        // Load related data if necessary (e.g., patient, items, conventions)
        $ficheNavette->load(['patient', 'items.Convention']); // Assuming 'prestation' is the relationship name in FicheNavetteItem to your convention model

        return Inertia::render('FicheNavettes/Edit', [ // Assuming you have a FicheNavettes/Edit.vue page
            'ficheNavette' => $ficheNavette,
            // You might need to pass other data like all patients, services, companies if the edit form needs them
        ]);
    }

    /**
     * Update the specified Fiche Navette in storage.
     */
    public function update(Request $request, FicheNavette $ficheNavette)
    {
        Log::info('FicheNavette update request data:', $request->all());

        // Define validation rules for update
        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'FNnumber' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fiche_navettes', 'FNnumber')->ignore($ficheNavette->id),
            ],
            'family_auth' => 'required|string|in:ascendant,descendant,conjoint,adherent,autre',
            
            // Beneficiary fields are required UNLESS family_auth is 'adherent'
            'first_name_beneficiary' => [
                Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
                'nullable',
                'string',
                'max:255',
            ],
            'last_name_beneficiary' => [
                Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
                'nullable',
                'string',
                'max:255',
            ],
            'phone_beneficiary' => [
                Rule::requiredIf(fn () => $request->input('family_auth') !== 'adherent'),
                'nullable',
                'string',
                'max:20',
            ],
            'email_beneficiary' => 'nullable|email|max:255',
            'address_beneficiary' => 'nullable|string|max:255',
            'prise_en_charge_number' => 'nullable|string|max:255',
            'number_mutuelle' => 'nullable|string|max:255',

            'fiche_date' => 'required|date',
            'base_price' => 'required|numeric|min:0',
            'final_price' => 'required|numeric|min:0',
            'patient_share' => 'required|numeric|min:0',
            'organisme_share' => 'required|numeric|min:0',
            'status' => 'required|string|max:255',
        ];

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $ficheNavette) {
            $ficheNavette->update([
                'patient_id' => $validated['patient_id'],
                'fiche_date' => $validated['fiche_date'],
                'FNnumber' => $validated['FNnumber'],
                'family_auth' => $validated['family_auth'],
                'base_price' => $validated['base_price'],
                'final_price' => $validated['final_price'],
                'patient_share' => $validated['patient_share'],
                'organisme_share' => $validated['organisme_share'],
                'status' => $validated['status'],
                'first_name_beneficiary' => $validated['first_name_beneficiary'] ?? null,
                'last_name_beneficiary' => $validated['last_name_beneficiary'] ?? null,
                'phone_beneficiary' => $validated['phone_beneficiary'] ?? null,
                'email_beneficiary' => $validated['email_beneficiary'] ?? null,
                'address_beneficiary' => $validated['address_beneficiary'] ?? null,
                'prise_en_charge_number' => $validated['prise_en_charge_number'] ?? null,
                'number_mutuelle' => $validated['number_mutuelle'] ?? null,
            ]);

            // If conventions can be updated with the FicheNavette, you'd handle syncing FicheNavetteItems here.
            // Example:
            // $ficheNavette->items()->delete(); // Remove old items
            // foreach ($request->input('convention_ids') as $conventionId) {
            //     FicheNavetteItem::create([
            //         'fiche_navette_id' => $ficheNavette->id,
            //         'prestation_id' => $conventionId,
            //         'prise_en_charge_date' => now(),
            //     ]);
            // }
        });

        return Redirect::back()->with('success', 'Fiche Navette updated successfully.');
    }

    /**
     * Remove the specified Fiche Navette from storage.
     */
     public function destroy(FicheNavette $ficheNavette)
    {
        try {
            $ficheNavette->delete();
            return Redirect::back()->with('success', 'Fiche Navette deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting Fiche Navette:', ['id' => $ficheNavette->id, 'error' => $e->getMessage()]);
            return Redirect::back()->with('error', 'Failed to delete Fiche Navette.');
        }
    }
}