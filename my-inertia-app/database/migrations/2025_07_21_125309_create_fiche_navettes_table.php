<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fiche_navettes', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('patient_id')->nullable(); // Not null foreign key
            $table->date('fiche_date'); // Not null
            // $table->string('status')->comment('The core status engine for a single service, e.g., scheduled, awaiting-payment, visa-granted'); // Not null
            $table->string('FNnumber')->nullable()->comment('Fiche Navette number, unique identifier for the navette'); // Nullable, unique
            $table->string('family_auth')->nullable()->comment('Family authorization number, if applicable'); // Nullable
            $table->string('first_name_beneficiary')->nullable()->comment('First name of the beneficiary'); // Nullable
            $table->string('last_name_beneficiary')->nullable()->comment('Last name of the beneficiary'); // Nullable
            $table->string('phone_beneficiary')->nullable()->comment('Phone number of the beneficiary'); // Nullable
            $table->string('email_beneficiary')->nullable()->comment('Email of the beneficiary'); // Nullable
            $table->string('address_beneficiary')->nullable()->comment('Address of the beneficiary'); // Nullable
           $table->string('prise_en_charge_number')->nullable()->comment('The crucial date from the B2B guarantee document'); // Nullable date
            $table->string('number_mutuelle')->nullable()->comment('Mutuelle number, if applicable'); // Nullable
            
            $table->decimal('base_price', 15, 2); // Not null
            $table->decimal('final_price', 15, 2)->comment('Price after all calculations'); // Not null
            $table->decimal('patient_share', 15, 2); // Not null
            $table->decimal('organisme_share', 15, 2); // Not null
            $table->string('status')->comment('e.g., scheduled, checked-in, completed, cancelled'); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_navettes');
    }
};
