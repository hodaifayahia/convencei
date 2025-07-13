<?php

namespace App\Imports;

use App\Models\Convention;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Str;

class ConventionsImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    use Importable;

    protected $companyId;
    protected $serviceId;

    public function __construct($companyId = null, $serviceId = null)
    {
        $this->companyId = $companyId;
        $this->serviceId = $serviceId;
    }

    public function model(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        // Get designation based on your actual column names
        $designation = $this->getDesignation($row);
        
        if (empty($designation)) {
            return null;
        }

        // Generate simple code
        $code = 'CONV_' . time() . '_' . Str::random(4);

        // Map your actual Excel columns to the database fields with proper numeric conversion
        $conventionData = [
            'service_id' => $this->serviceId,
            'company_id' => $this->companyId,
            'code' => $code,
            'designation_prestation' => $designation,
            'montant_ht' => $this->convertToNumeric($row['montant_ht'] ?? null),
            'montant_global_ttc' => $this->convertToNumeric($row['montant_global_ttc'] ?? null),
            'montant_prise_charge_entreprise' => $this->convertToNumeric($row['montant_prise_charge_entreprise'] ?? null),
            'montant_prise_charge_beneficiaire' => $this->convertToNumeric($row['montant_prise_charge_beneficiaire'] ?? null),
        ];

        return new Convention($conventionData);
    }

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
            if (isset($row[$key]) && !empty(trim($row[$key]))) {
                return trim($row[$key]);
            }
        }

        return null;
    }

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

        // If it's a string that starts with '=', it's likely a formula that wasn't calculated
        if (is_string($value) && strpos($value, '=') === 0) {
            // Log the formula for debugging
            \Log::warning('Excel formula detected in import', ['formula' => $value]);
            return null; // or return 0 if you prefer
        }

        // Try to extract numeric value from string
        $cleaned = preg_replace('/[^0-9.,\-]/', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
