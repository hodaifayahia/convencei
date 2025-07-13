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
        Schema::create('convention', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key

            // NOM_DOM (Domain Name)
              $table->foreignId('service_id')
                  ->nullable() // Services can exist without a company initially, or be unassigned
                  ->constrained() // Creates a foreign key constraint
                  ->onDelete('set null'); // If a company is deleted, set company_id to null for its services
            $table->foreignId('company_id')
                 ->nullable() // Services can exist without a company initially, or be unassigned
                  ->constrained() // Creates a foreign key constraint
                  ->onDelete('set null'); // If a company is deleted, set company_id to null for its services
            // Code
            $table->string('code')->unique()->comment('Code de la Prestation'); // Assuming code is unique
                        //$table->string('nom_sdom')->nullable()->comment('Nom du Sous-Domaine');


            // Désignation de la Prestation (Service/Prestation Designation)
            $table->text('designation_prestation')->comment('Désignation de la Prestation');

            // Montant HT (Amount Excluding Tax)
            $table->decimal('montant_ht', 10, 2)->nullable()->comment('Montant Hors Taxe'); // 10 total digits, 2 after decimal

            // Montant global TTC (Global Amount Including Tax)
            $table->decimal('montant_global_ttc', 10, 2)->nullable()->comment('Montant global Toutes Taxes Comprises');

            // Montant pris en charge par l'entreprise (Amount covered by the company)
            // Note: The "80% TTC Plafonnés à 250.000.00DA TTC" is a business rule, not a database column.
            // The column stores the *calculated* amount.
            $table->decimal('montant_prise_charge_entreprise', 10, 2)->nullable()->comment('Montant pris en charge par l\'entreprise');

            // Montant pris en charge par le bénéficiaire (Amount covered by the beneficiary)
            // Note: The "20% EN TTC" is a business rule, not a database column.
            // The column stores the *calculated* amount.
            $table->decimal('montant_prise_charge_beneficiaire', 10, 2)->nullable()->comment('Montant pris en charge par le bénéficiaire');

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convention');
    }
};