<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient; // This import is good

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
       'insured_id', // This is the new field for the insured patient
        'prise_en_charge_number',
        'number_mutuelle',
    ];

    // Define relationship to Patient
    public function patient()
    {
        // Laravel should automatically use the 'patient_db' connection specified in the Patient model
        // when this relationship is resolved. No changes needed here.
        return $this->belongsTo(Patient::class , 'patient_id', 'id');
    }
  public function insured()
{
    return $this->belongsTo(Patient::class, 'insured_id', 'id'); // This is the new field for the insured patient
}

    // Define relationship to FicheNavetteItem
    public function items()
    {
        return $this->hasMany(FicheNavetteItem::class);
    }
}