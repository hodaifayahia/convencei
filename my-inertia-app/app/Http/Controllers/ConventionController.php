<?php

namespace App\Http\Controllers;

use App\Models\Convention;
use App\Models\Service; // For dropdowns
use App\Models\Company; // For dropdowns
use App\Models\Patient; // For dropdowns
use App\Models\FicheNavette; // For next FN number
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel; // Make sure to install maatwebsite/excel
use App\Imports\ConventionsImport; // We'll create this class next


class ConventionController extends Controller
{
    /**
     * Display a listing of the conventions.
     */
   public function index(Request $request)
    {
        // Get filters from the request, provide defaults
        $companyId = $request->query('company_id');
        $serviceId = $request->query('service_id');
        $perPage = $request->get('perPage', 15); // Default 15 items per page
        $search = $request->get('search', '');

        $conventionsQuery = Convention::query()
            ->with(['service', 'company']); // Eager load relationships

        // Apply filters
        if ($companyId) {
            $conventionsQuery->where('company_id', $companyId);
        }
        if ($serviceId) {
            $conventionsQuery->where('service_id', $serviceId);
        }

        // Apply search
        $conventionsQuery = $this->buildSearchQuery($conventionsQuery, $search);

        // Use pagination instead of get()
        $conventions = $conventionsQuery
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString(); // Preserve query parameters in pagination links

        // Only load dropdown data when needed
        // Assuming 'Company' and 'Service' are for filter dropdowns that need all options
        $allServices = Service::orderBy('name')->get(['id', 'name']);
        $allCompanies = Company::orderBy('name')->get(['id', 'name']);
        
        // Optimize patient loading - only load first 100 or use lazy loading (if used in dropdown directly)
        // If 'allPatients' is for a search/select input, consider an API endpoint for dynamic loading
        $allPatients = Patient::limit(50)->get();

        $nextFNnumber = $this->getNextFNnumber();

        // Fetch selected company and service for initial state of filters
        $selectedService = $serviceId ? Service::find($serviceId) : null;
        $selectedCompany = $companyId ? Company::find($companyId) : null;

        return Inertia::render('Convention/Index', [ // Ensure this matches your actual page path
            'conventions' => $conventions, // This now includes pagination data
            'allServices' => $allServices,
            'allCompanies' => $allCompanies,
            'nextFNnumber' => $nextFNnumber,
            'selectedService' => $selectedService, // Pass selected service object if needed for display
            'selectedCompany' => $selectedCompany, // Pass selected company object if needed for display
            'allPatients' => $allPatients,
            'filters' => [ // Pass the current active filters back to the frontend
                'search' => $search,
                'perPage' => (int)$perPage, // Cast to int to ensure type consistency
                'company_id' => $companyId ? (int)$companyId : null, // Pass ID back
                'service_id' => $serviceId ? (int)$serviceId : null, // Pass ID back
            ],
            // You might want to pass 'flash' messages here if you use them for CRUD operations
            // 'flash' => session('flash') 
        ]);
    }

    /**
     * Builds the search query for conventions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSearchQuery($query, $search)
    {
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('designation_prestation', 'like', "%{$search}%")
                  ->orWhereHas('company', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('service', function($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }
    private function getNextFNnumber(): string
    {
        $currentYear = Carbon::now()->year;

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



   
    /**
     * Store a newly created convention in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'code' => ['required', 'string', 'max:255'],
            'designation_prestation' => ['required', 'string', 'max:255'],
            'montant_ht' => ['nullable', 'numeric'],
            'montant_global_ttc' => ['nullable', 'numeric'],
            'montant_prise_charge_entreprise' => ['nullable', 'numeric'],
            'montant_prise_charge_beneficiaire' => ['nullable', 'numeric'],
        ]);

        Convention::create($request->all());

        // Redirect back with context parameters to maintain filter
        return redirect()->route('conventions.index', [
            'company_id' => $request->company_id,
            'service_id' => $request->service_id
        ])->with('success', 'Convention created successfully!');
    }

    /**
     * Update the specified convention in storage.
     */
    public function update(Request $request, Convention $convention)
    {
        $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'code' => ['required', 'string', 'max:255'],
            'designation_prestation' => ['required', 'string', 'max:255'],
            'montant_ht' => ['nullable', 'numeric'],
            'montant_global_ttc' => ['nullable', 'numeric'],
            'montant_prise_charge_entreprise' => ['nullable', 'numeric'],
            'montant_prise_charge_beneficiaire' => ['nullable', 'numeric'],
        ]);

        $convention->update($request->all());

         return back()->with([
            'company_id' => $request->company_id,
            'service_id' => $request->service_id
        ])->with('success', 'Convention updated successfully!');
        // Redirect back with context parameters to maintain filter
        // return redirect()->route('conventions.index', [
        //     'company_id' => $request->company_id,
        //     'service_id' => $request->service_id
        // ];
    }

    /**
     * Remove the specified convention from storage.
     */
    public function destroy(Convention $convention)
    {
        $companyId = $convention->company_id;
        $serviceId = $convention->service_id;

        $convention->delete();

        // Redirect back with context parameters to maintain filter
          return back()->with([
            'company_id' => $companyId,
            'service_id' => $serviceId
        ])->with('success', 'Convention deleted successfully!');
    }

    /**
     * Handle Excel import for conventions.
     */
 public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        'company_id' => 'nullable|exists:companies,id',
        'service_id' => 'nullable|exists:services,id',
    ]);

    try {
        Log::info('Starting Excel import', [
            'file_name' => $request->file('file')->getClientOriginalName(),
            'company_id' => $request->company_id,
            'service_id' => $request->service_id,
        ]);

        $import = new ConventionsImport($request->company_id, $request->service_id);
        
        // Import the file
        Excel::import($import, $request->file('file'));

        $redirectParams = [];
        if ($request->company_id) {
            $redirectParams['company_id'] = $request->company_id;
        }
        if ($request->service_id) {
            $redirectParams['service_id'] = $request->service_id;
        }

        return redirect()->route('conventions.index', $redirectParams)
            ->with('success', 'Excel file imported successfully!');

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        
        Log::error('Excel import validation failed', [
            'failures' => $failures
        ]);
        
        $errorMessages = [];
        foreach ($failures as $failure) {
            $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
        }
        
        return back()->withErrors([
            'file' => 'Import validation failed: ' . implode(' | ', $errorMessages)
        ]);

    } catch (\Exception $e) {
        Log::error('Excel import failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $request->file('file')->getClientOriginalName()
        ]);
        
        return back()->withErrors([
            'file' => 'Import failed: ' . $e->getMessage()
        ]);
    }
}


}