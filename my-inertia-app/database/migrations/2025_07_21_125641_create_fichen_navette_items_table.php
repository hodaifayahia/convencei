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
        Schema::create('fiche_navette_items', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            // Ensure 'fiche_navettes' table exists before this migration runs.
            $table->foreignId('fiche_navette_id')->constrained('fiche_navettes')->onDelete('cascade'); // Not null foreign key
            // Ensure 'prestations' table exists before this migration runs.
            $table->foreignId('prestation_id')->constrained('convention')->onDelete('cascade'); // Not null foreign key
            
            // Uncomment and ensure 'users' and 'modalities' tables exist if you use these
            // $table->foreignId('primary_clinician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            // $table->foreignId('assistant_clinician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            // $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            // $table->foreignId('modality_id')->nullable()->constrained('modalities')->onDelete('set null'); // Nullable foreign key
            
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_navette_items');
    }
};

