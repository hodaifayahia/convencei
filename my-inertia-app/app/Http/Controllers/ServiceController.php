<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Convention;
use App\Models\FicheNavette;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
  public function index(Request $request)
    {
        $companyId = $request->query('company_id');
        $serviceId = $request->query('service_id');
        $perPage = $request->get('perPage', 15);
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

        // Add search functionality for conventions
        if ($search) {
            $conventionsQuery->where(function($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('designation_prestation', 'like', "%{$search}%")
                    ->orWhereHas('company', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $conventions = $conventionsQuery
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString(); // Preserve query parameters in pagination links

        // Fetch all services, potentially filtered by company if `company_id` is present,
        // otherwise fetch all for the general service list.
        $servicesQuery = Service::query()->orderBy('name');
        if ($companyId) {
            $servicesQuery->where('company_id', $companyId);
        }
        $services = $servicesQuery->get(['id', 'name', 'company_id']);


        $allCompanies = Company::orderBy('name')->get(['id', 'name', 'abbreviation']); // Added abbreviation for CompanyCard

        // Optimize patient loading: only load if specifically needed for a feature,
        // or consider lazy loading in the frontend if the list is very large.
        // Limiting to 100 for display might be fine, but confirm actual usage.

        $nextFNnumber = $this->getNextFNnumber();
        // dd($nextFNnumber);
        // Fetch selected items for context.
        // Corrected the `selectedService` fetch logic.
        $selectedService = $serviceId ? Service::find($serviceId) : null;
        $selectedCompany = $companyId ? Company::find($companyId) : null;

        return Inertia::render('Services/Index', [
            'conventions' => $conventions,
            'services' => $services, // Pass the services that are filtered by selected company (or all)
            'allCompanies' => $allCompanies,
            'nextFNnumber' => $nextFNnumber,
            'selectedService' => $selectedService,
            'selectedCompany' => $selectedCompany,
            'filters' => [
                'search' => $search,
                'perPage' => (int)$perPage,
                'company_id' => $companyId ? (int)$companyId : null,
                'service_id' => $serviceId ? (int)$serviceId : null,
            ],
            'flash' => session('flash')
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

    // ... (store, update, destroy methods remain the same) ...
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

        return redirect()->route('services.index', ['company_id' => $request->company_id])
                         ->with('success', 'Service created successfully!');
    }

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

        return redirect()->route('services.index', ['company_id' => $request->company_id])
                         ->with('success', 'Service updated successfully!');
    }

    public function destroy(Service $service)
    {
        $companyId = $service->company_id;

        $service->delete();

        return redirect()->route('services.index', ['company_id' => $companyId])
                         ->with('success', 'Service deleted successfully!');
    }
}