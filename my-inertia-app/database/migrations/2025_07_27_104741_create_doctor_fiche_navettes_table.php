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
        Schema::create('doctor_fiche_navettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_navette_id')->constrained('fiche_navettes')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable(); // Not null foreign key
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_fiche_navettes');
    }
};
