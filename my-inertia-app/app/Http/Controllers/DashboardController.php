<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\FicheNavette;
use App\Models\Company;
use App\Models\Service;
use App\Models\Convention;
use Inertia\Inertia; // Assuming you are using Inertia.js as per your Vue template

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Fetch counts for various models
        $totalPatients = Patient::count();
        $totalFicheNavettes = FicheNavette::count();
        $totalCompanies = Company::count();
        $totalServices = Service::count();
        $totalConventions = Convention::count();

        // Fetch aggregated data
        $totalFicheNavetteRevenue = FicheNavette::sum('final_price');
        $totalPatientShare = FicheNavette::sum('patient_share');
        $totalOrganismeShare = FicheNavette::sum('organisme_share');

        // Fetch recent FicheNavettes (e.g., last 5)
        // Ensure 'patient' relationship exists and is correctly defined in FicheNavette model
        $recentFicheNavettes = FicheNavette::with('patient')
                                         ->orderBy('created_at', 'desc')
                                         ->limit(5)
                                         ->get();

        // Fetch recent Patients (e.g., last 5)
        $recentPatients = Patient::orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();

        // Return the data to the Inertia.js view
        return Inertia::render('Dashboard', [
            'stats' => [
                'totalPatients' => $totalPatients,
                'totalFicheNavettes' => $totalFicheNavettes,
                'totalCompanies' => $totalCompanies,
                'totalServices' => $totalServices,
                'totalConventions' => $totalConventions,
                // Format currency values for display
                'totalFicheNavetteRevenue' => number_format($totalFicheNavetteRevenue, 2, '.', ','),
                'totalPatientShare' => number_format($totalPatientShare, 2, '.', ','),
                'totalOrganismeShare' => number_format($totalOrganismeShare, 2, '.', ','),
            ],
            'recentFicheNavettes' => $recentFicheNavettes,
            'recentPatients' => $recentPatients,
        ]);
    }
}
