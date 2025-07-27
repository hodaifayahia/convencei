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
        Schema::table('services', function (Blueprint $table) {
            // First, drop the old single-column unique index if it exists
            // (This is crucial if you previously had a unique index just on 'name')
            $table->dropUnique('services_name_unique'); // Or whatever the existing unique index name is

            // Add the new composite unique index
            $table->unique(['name', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop the composite unique index
            $table->dropUnique(['name', 'company_id']);

            // Optionally, if you want to revert to the old state, re-add the single unique index
            // $table->unique('name');
        });
    }
};