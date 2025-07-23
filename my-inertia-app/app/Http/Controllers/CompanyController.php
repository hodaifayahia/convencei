<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index()
    {
        $companies = Company::orderBy('created_at', 'desc')->get();

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:255', 'unique:companies,abbreviation'],
            'augmentation' => ['nullable', 'numeric'],
            'pourcentage_company' => ['nullable', 'numeric', 'between:0,100'],
            'pourcentage_benefit' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        Company::create($request->all());

        return redirect()->route('companies.index')
                         ->with('success', 'Company created successfully!');
    }

    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:255', Rule::unique('companies')->ignore($company->id, 'abbreviation')],
            'augmentation' => ['nullable', 'numeric'],
            'pourcentage_company' => ['nullable', 'numeric', 'between:0,100'],
            'pourcentage_benefit' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $company->update($request->all());

        return redirect()->route('companies.index')
                         ->with('success', 'Company updated successfully!');
    }

    /**
     * Remove the specified company from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')
                         ->with('success', 'Company deleted successfully!');
    }
}