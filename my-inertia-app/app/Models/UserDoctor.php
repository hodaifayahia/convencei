<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDoctor extends Model
{
    // Specify the connection if it's different from the default
    protected $connection = 'mysql_main'; // Adjust this if you have a specific connection for doctors

    // Specify the table name if it differs from the model name
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        
    ];

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'user_id', 'id');
    }
}
