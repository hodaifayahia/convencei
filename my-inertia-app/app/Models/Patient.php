<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\FicheNavette; // Import FicheNavette model
class Patient extends Model
{
     protected $connection = 'mysql_patients'; // <-- THIS IS KEY
        protected $table = 'patients'; // Specify the table name if it differs from the model name
    protected $fillable = [
        'Firstname',
        'Lastname',
        'Parent',
        'phone',
        'dateOfBirth',
        'Idnum',
        'gender',
        'nss',
    ];
     public function ficheNavettes()
    {
        // The foreign key 'patient_id' is on the 'fiche_navettes' table
        // The local key 'id' is on the 'patients' table (this model's table)
        // You might explicitly define the foreign key if it's not 'fiche_navette_id'
        return $this->hasMany(FicheNavette::class, 'patient_id', 'id');
    }
}
