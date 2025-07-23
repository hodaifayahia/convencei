<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response; // Import Response for clarity
use Illuminate\Http\RedirectResponse; // Import RedirectResponse for clarity


class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
       public function index()
    {
        // Fetch all patients from the 'mysql_patients' connection
        // The Patient model is already configured to use 'mysql_patients'
        $patients = Patient::orderBy('created_at', 'desc')->paginate(10); // Example: paginate for larger datasets

        // Render the Patients/Index React component with the patient data
        return Inertia::render('Patients/Index', [
            'patients' => $patients,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // This is typically for a dedicated 'create' page, often not needed if using a modal
        return Inertia::render('Patients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'Firstname' => ['required', 'string', 'max:255'],
            'Lastname' => ['required', 'string', 'max:255'],
            'Parent' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'dateOfBirth' => ['required', 'date'],
            'gender' => ['nullable', 'string', 'max:255'],
            // Ensure Idnum is unique ONLY across the patients table for a new patient
            'Idnum' => ['nullable', 'string', 'max:255', 'unique:mysql_patients.patients,Idnum'],
            'nss' => ['nullable', 'string', 'max:255'],
        ]);

        // Create a new patient record
        $patient = Patient::create($validatedData);

        // Instead of redirecting to index, redirect back to the current page
        // with the new patient's ID in a flash message.
        return back()->with('flash', [
            'message' => 'Patient created successfully.',
            'newPatientId' => $patient->id, // Send the ID back
            // You can also send the full patient object if needed:
            // 'newPatient' => $patient->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): Response
    {
        // This is for viewing a single patient, typically a separate page
        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient): Response
    {
        // This is typically for a dedicated 'edit' page, often not needed if using a modal
        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'Firstname' => ['required', 'string', 'max:255'],
            'Lastname' => ['required', 'string', 'max:255'],
            'Parent' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'dateOfBirth' => ['required', 'date'],
            'gender' => ['nullable', 'string', 'max:255'],
            // Unique except for the current patient when updating
            'Idnum' => ['nullable', 'string', 'max:255', 'unique:mysql_patients.patients,Idnum,' . $patient->id],
            'nss' => ['nullable', 'string', 'max:255'],
        ]);

        // Update the patient record
        $patient->update($validatedData);

        // Redirect back to the current page with a success message.
        // Inertia will automatically update the component's props if you return back()
        // and the prop name (e.g., 'allPatients') is part of the current page's props.
        return back()->with('flash', [
            'message' => 'Patient updated successfully.',
            'updatedPatientId' => $patient->id, // Optional: send ID back
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        // Delete the patient record
        $patient->delete();

        // Redirect back to the patients index with a success message
        return back()->with('success', 'Patient deleted successfully.');
    }
}