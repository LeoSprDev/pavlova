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
        Schema::table('budget_lignes', function (Blueprint $table) {
            $table->decimal('montant_engage', 15, 2)->default(0)->after('montant_depense_reel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_lignes', function (Blueprint $table) {
            $table->dropColumn('montant_engage');
        });
    }
};
