<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This method will drop the old beneficiary columns and add the new insured_id foreign key.
     */
    public function up(): void
    {
        Schema::table('fiche_navettes', function (Blueprint $table) {
            // Drop the existing beneficiary columns
            // Ensure these columns exist before attempting to drop them to avoid errors
            if (Schema::hasColumn('fiche_navettes', 'first_name_beneficiary')) {
                $table->dropColumn('first_name_beneficiary');
            }
            if (Schema::hasColumn('fiche_navettes', 'last_name_beneficiary')) {
                $table->dropColumn('last_name_beneficiary');
            }
            if (Schema::hasColumn('fiche_navettes', 'phone_beneficiary')) {
                $table->dropColumn('phone_beneficiary');
            }
            if (Schema::hasColumn('fiche_navettes', 'email_beneficiary')) {
                $table->dropColumn('email_beneficiary');
            }
            if (Schema::hasColumn('fiche_navettes', 'address_beneficiary')) {
                $table->dropColumn('address_beneficiary');
            }

            // Add the new 'insured_id' foreign key column
            // It's nullable and links to the 'patients' table, setting to null on patient deletion
                       $table->foreignId('insured_id')->nullable(); // Not null foreign key

        });
    }

    /**
     * Reverse the migrations.
     * This method will revert the changes made in the 'up' method,
     * dropping the 'insured_id' and re-adding the old beneficiary columns.
     */
    public function down(): void
    {
        Schema::table('fiche_navettes', function (Blueprint $table) {
            // Drop the 'insured_id' foreign key and column
            $table->dropColumn('insured_id'); // Then drop the column

            // Re-add the original beneficiary columns (as they were before this migration)
            // Note: Data in these columns will be lost if this migration is rolled back after running.
            $table->string('first_name_beneficiary')->nullable()->comment('First name of the beneficiary');
            $table->string('last_name_beneficiary')->nullable()->comment('Last name of the beneficiary');
            $table->string('phone_beneficiary')->nullable()->comment('Phone number of the beneficiary');
            $table->string('email_beneficiary')->nullable()->comment('Email of the beneficiary');
            $table->string('address_beneficiary')->nullable()->comment('Address of the beneficiary');
        });
    }
};
