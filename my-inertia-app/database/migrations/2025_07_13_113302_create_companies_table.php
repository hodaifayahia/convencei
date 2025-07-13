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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Assuming a full company name as well
            $table->string('abbreviation')->unique()->nullable(); // 'abrviation de compony'
            $table->decimal('augmentation', 8, 2)->nullable(); // 'augmentation' (e.g., 12.50)
            $table->decimal('pourcentage_company', 5, 2)->nullable(); // 'pourcentage Compony' (e.g., 25.00 for 25%)
            $table->decimal('pourcentage_benefit', 5, 2)->nullable(); // 'procenatage benfit' (e.g., 10.00 for 10%)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};