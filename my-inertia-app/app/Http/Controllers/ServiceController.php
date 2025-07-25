<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Company; // Import Company model
use App\Models\Patient; // Import Company model
use App\Models\Convention; // Import Company model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\FicheNavette;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
   
public function index(Request $request)
{
    $selectedCompany = null;
    $services = Service::query();
    $conventions = Convention::query(); // Start convention query
        $nextFNnumber = $this->getNextFNnumber();

    if ($request->has('company_id')) {
        $selectedCompany = Company::find($request->input('company_id'));
        if ($selectedCompany) {
            $services->where('company_id', $selectedCompany->id);
            $conventions->where('company_id', $selectedCompany->id); // Filter conventions by company
        }
    }
$allPatients = Patient::all(); // This is where the API/DB call happens on the backend
    // Eager load relationships for display
    $services = $services->with('company')->get();
    $conventions = $conventions->with(['service', 'company'])->get(); // Eager load service and company for conventions

    return Inertia::render('Services/Index', [
        'services' => $services,
         'allPatients' => $allPatients, // Pass all patients for the dropdown
        'selectedCompany' => $selectedCompany,
        'nextFNnumber' => $nextFNnumber, // Pass the next FN
        'allCompanies' => Company::all(), // Ensure all companies are passed for the select input
        'conventions' => $conventions, // Pass the filtered conventions
        'flash' => session('flash') // Make sure flash messages are passed
    ]);
}
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
    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        Service::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
        ]);

        // Redirect back with the company_id to maintain the filter
        return redirect()->route('services.index', ['company_id' => $request->company_id])
                         ->with('success', 'Service created successfully!');
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $service->update([
            'name' => $request->name,
            'company_id' => $request->company_id,
        ]);

        // Redirect back with the company_id to maintain the filter
        return redirect()->route('services.index', ['company_id' => $request->company_id])
                         ->with('success', 'Service updated successfully!');
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service)
    {
        $companyId = $service->company_id; // Get company_id before deleting for redirect

        $service->delete();

        // Redirect back with the company_id to maintain the filter
        return redirect()->route('services.index', ['company_id' => $companyId])
                         ->with('success', 'Service deleted successfully!');
    }
}