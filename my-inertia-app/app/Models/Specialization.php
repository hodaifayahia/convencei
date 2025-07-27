<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    // Specify the connection if it's different from the default
    protected $connection = 'mysql_main'; // Adjust this if you have a specific connection for specializations

    // Specify the table name if it differs from the model name
    protected $table = 'specializations';

    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    // Define any relationships if necessary
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'specialization_id', 'id');
    }
}
