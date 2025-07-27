<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // Make sure this is imported

class FicheNavette extends Model
{
    use HasFactory; // Ensure HasFactory is used if you generate factories

    protected $table = 'fiche_navettes';

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
        'insured_id',
        'prise_en_charge_number',
        'number_mutuelle',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }

    public function insured()
    {
        return $this->belongsTo(Patient::class, 'insured_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(FicheNavetteItem::class);
    }

   
}