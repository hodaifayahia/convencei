<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserDoctor;
use App\Models\Specialization;

class Doctor extends Model
{
    // Specify the connection if it's different from the default
    protected $connection = 'mysql_main'; // Adjust this if you have a specific connection for doctors

    // Specify the table name if it differs from the model name
    protected $table = 'doctors';


    protected $fillable = [
        'specialization_id',
        'number_of_patient',
        'frequency',
        'specific_date',
        'notes',
        'patients_based_on_time',
        'time_slot',
        'appointment_booking_window',
        'created_by',
        'user_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'schedule_id',
        'include_time',
    ];
     public function user()
    {
        return $this->belongsTo(UserDoctor::class, 'user_id', 'id');
    }
        public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
    
   

 
}
