<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'augmentation',
        'pourcentage_company',
        'pourcentage_benefit',
    ];

    // If you want to cast numeric fields to floats automatically
    protected $casts = [
        'augmentation' => 'float',
        'pourcentage_company' => 'float',
        'pourcentage_benefit' => 'float',
    ];
}