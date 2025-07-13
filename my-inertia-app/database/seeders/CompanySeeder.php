<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Tech Solutions Inc.',
            'abbreviation' => 'TSI',
            'augmentation' => 1500.00,
            'pourcentage_company' => 75.00,
            'pourcentage_benefit' => 25.00,
        ]);
        Company::create([
            'name' => 'Global Innovations Ltd.',
            'abbreviation' => 'GIL',
            'augmentation' => 500.00,
            'pourcentage_company' => 60.00,
            'pourcentage_benefit' => 40.00,
        ]);
    }
}