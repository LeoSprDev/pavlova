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
        Schema::table('demande_devis', function (Blueprint $table) {
            // Add user_id column, make it nullable for existing records,
            // but ideally, existing records should be updated if possible.
            // It should be constrained to the users table.
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            // If you want to ensure it's always set for new records, handle this at application level.
            // onDelete('set null') means if the user is deleted, the user_id on demande_devis becomes null.
            // Consider onDelete('cascade') if a demande should be deleted when its creator is deleted,
            // or onDelete('restrict') to prevent user deletion if they have demandes. 'set null' is often a safe default.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            // Drop foreign key first if it was explicitly named.
            // If not named, Laravel generates one like 'demande_devis_user_id_foreign'.
            // It's safer to use the array syntax for dropping foreign keys.
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
