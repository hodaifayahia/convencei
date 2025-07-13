<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service; // Import your Service model

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create(['name' => 'Basic Consultation']);
        Service::create(['name' => 'Premium Support']);
        Service::create(['name' => 'Emergency Service']);
        Service::create(['name' => 'Follow-up Check']);
    }
}