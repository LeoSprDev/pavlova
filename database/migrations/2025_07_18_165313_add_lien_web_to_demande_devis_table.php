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
            $table->text('lien_web')->nullable()->after('justification_besoin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            $table->dropColumn('lien_web');
        });
    }
};
