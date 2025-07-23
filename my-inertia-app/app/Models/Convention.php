<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convention extends Model
{
    use HasFactory;

    // Ensure this matches your migration table name
    protected $table = 'convention';

    protected $fillable = [
        'service_id',
        'company_id',
        'code',
        'designation_prestation',
        'montant_ht',
        'montant_global_ttc',
        'montant_prise_charge_entreprise',
        'montant_prise_charge_beneficiaire',
        'sheet_type', // Add this field
    ];

    protected $casts = [
        'montant_ht' => 'float',
        'montant_global_ttc' => 'float',
        'montant_prise_charge_entreprise' => 'float',
        'montant_prise_charge_beneficiaire' => 'float',
    ];

    // Define relationships
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function patient()
{
    return $this->belongsTo(Patient::class);
}
}
