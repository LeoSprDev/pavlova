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
            if (!Schema::hasColumn('demande_devis', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('budget_ligne_id')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            if (Schema::hasColumn('demande_devis', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
