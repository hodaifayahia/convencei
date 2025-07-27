<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // Use Pivot class
use Illuminate\Support\Facades\DB; // Needed for cross-DB

class DoctorFicheNavette extends Pivot
{
    protected $table = 'doctor_fiche_navettes';
   protected $fillable = [
        'doctor_id',
        'fiche_navette_id',
    ];
   
    public function ficheNavette()
    {
        // Explicitly specifying the connection for related models can sometimes help clarity,
        // though Laravel might infer it from the model's connection property.
        return $this->belongsTo(FicheNavette::class, 'fiche_navette_id', 'id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id');
    }
}