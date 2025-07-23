<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient; // Add this import

class FicheNavette extends Model
{
    protected $fillable = [
        'patient_id',
        'fiche_date',
        'base_price',
        'final_price',
        'patient_share',
        'organisme_share',
        'status',
        'FNnumber',
        'family_auth',
        'first_name_beneficiary',
        'last_name_beneficiary',
        'phone_beneficiary',
        'prise_en_charge_number',
        'number_mutuelle',
    ];

    // Define relationship to Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Define relationship to FicheNavetteItem
    public function items()
    {
        return $this->hasMany(FicheNavetteItem::class);
    }
}
