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
        Schema::create('services', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('name')->unique(); // Name of the service, should be unique
            $table->text('description')->nullable(); // Optional description
             $table->foreignId('company_id')
                  ->nullable() // Services can exist without a company initially, or be unassigned
                  ->constrained() // Creates a foreign key constraint
                  ->onDelete('set null'); // If a company is deleted, set company_id to null for its services
            $table->timestamps(); // Adds `created_at` and `updated_at` columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};