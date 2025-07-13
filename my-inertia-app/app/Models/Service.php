<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Service extends Model
{
    use HasFactory;

    // Specify the table if it's not the plural form of the model name
    protected $table = 'services';

    // Define which attributes are mass assignable
    protected $fillable = [
        'name',
        'description', // Not in your migration
         'company_id',       // Not in your migration
        // 'duration',    // Not in your migration
        // 'is_active',   // Not in your migration
        // 'category',    // Not in your migration
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}