<?php

namespace App\Imports;

use App\Models\Convention;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\Importable;
use App\Models\Company; // Ensure this model is correct, it was 'Organisme' in my previous suggestion.
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Import Log facade for warnings

class ConventionsImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    use Importable;

    protected $companyId;
    protected $serviceId;
    protected $counter; // Property for the counter

    public function __construct($companyId = null, $serviceId = null)
    {
        $this->companyId = $companyId;
        $this->serviceId = $serviceId;
        // Initialize the counter to -1 so the first increment makes it 0.
        $this->counter = -1; 
    }

    public function model(array $row)
    {
        // Increment the counter for each row processed.
        // If initialized to -1, the first row will make it 0.
        $this->counter++; 

        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        // Get designation based on your actual column names
        $designation = $this->getDesignation($row);
        
        if (empty($designation)) {
            Log::warning('Skipping row due to empty designation', ['row' => $row, 'counter' => $this->counter]);
            return null;
        }

        // Fetch company abbreviation
        $company = Company::find($this->companyId);
        // Default to 'UNKNOWN' if company not found, or handle this error more robustly if companyId is mandatory.
        $abrv = $company ? $company->abbreviation : 'UNKNOWN'; 

        // Generate code with abbreviation, service ID, and the new counter.
        // Ensure serviceId is not null before appending.
        $code = $abrv . '_' . ($this->serviceId ?? 'NOSERVICE') . '_' . $this->counter;

        // If the Excel sheet has a 'code' column, prefer it, otherwise use the generated one.
        $finalCode = $row['code'] ?? $code;

        // Map your actual Excel columns to the database fields with proper numeric conversion
        $conventionData = [
            'service_id' => $this->serviceId,
            'company_id' => $this->companyId,
            'code' => $finalCode,
            'designation_prestation' => $designation,
            'montant_ht' => $this->convertToNumeric($row['montant_ht'] ?? null),
            'montant_global_ttc' => $this->convertToNumeric($row['montant_global_ttc'] ?? null),
            'montant_prise_charge_entreprise' => $this->convertToNumeric($row['montant_prise_charge_entreprise'] ?? null),
            'montant_prise_charge_beneficiaire' => $this->convertToNumeric($row['montant_prise_charge_beneficiaire'] ?? null),
            // Add other fields as necessary from your Convention model
        ];

        return new Convention($conventionData);
    }

    /**
     * Attempts to find the designation from various possible column names.
     *
     * @param array $row
     * @return string|null
     */
    private function getDesignation(array $row)
    {
        $possibleKeys = [
            'designations_des_actes',
            'clinique_des_oasis',
            'radiolgie',
            'consultation',
            'designation_prestation',
            'designation',
            'prestation',
            'acte'
        ];

        foreach ($possibleKeys as $key) {
            // Check if the key exists and its trimmed value is not empty
            if (isset($row[$key]) && !empty(trim($row[$key]))) {
                return trim($row[$key]);
            }
        }

        return null;
    }

    /**
     * Converts a value to a numeric (float) type, handling various formats and nulls.
     *
     * @param mixed $value
     * @return float|null
     */
    private function convertToNumeric($value)
    {
        // Handle null or empty values
        if ($value === null || $value === '') {
            return null;
        }

        // If it's already numeric, return as float
        if (is_numeric($value)) {
            return (float) $value;
        }

        // If it's a string that starts with '=', it's likely a formula that wasn't calculated.
        // With WithCalculatedFormulas, this should ideally not happen for calculated cells,
        // but it's a good fallback for literal formula strings.
        if (is_string($value) && strpos($value, '=') === 0) {
            Log::warning('Excel formula string detected in import for numeric field. Value: ' . $value);
            return null; // Or return 0, depending on desired behavior for uncalculated formulas
        }

        // Try to extract numeric value from string, handling comma as decimal separator
        // and removing any non-numeric, non-comma, non-dot, non-minus characters.
        $cleaned = preg_replace('/[^0-9.,\-]/', '', (string)$value);
        $cleaned = str_replace(',', '.', $cleaned); // Convert comma decimal to dot decimal

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}