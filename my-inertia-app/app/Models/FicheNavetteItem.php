<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FicheNavette;
use App\Models\Prestation; // Assuming you have a Prestation model

class FicheNavetteItem extends Model
{
    protected $table = 'fiche_navette_items'; // Specify the table name if different
    protected $fillable = [
        'fiche_navette_id',
        'prestation_id',
    ];

    // Define relationships if needed
    public function ficheNavette()
    {
        return $this->belongsTo(FicheNavette::class);
    }

    public function Convention()
    {
        return $this->belongsTo(Convention::class, 'prestation_id'); // Ensure 'prestation_id' is the foreign key column
    }
}
