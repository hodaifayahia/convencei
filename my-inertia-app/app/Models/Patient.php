<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// No need to import FicheNavette model here unless you directly use FicheNavette in this file
// use App\Models\FicheNavette;

class Patient extends Model
{
    // THIS IS KEY
    protected $connection = 'mysql_main';
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