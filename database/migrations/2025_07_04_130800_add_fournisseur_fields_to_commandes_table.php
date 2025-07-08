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
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('fournisseur_nom')->nullable()->after('statut');
            $table->decimal('montant_ht', 15, 2)->nullable()->after('fournisseur_email');
            $table->decimal('montant_ttc', 15, 2)->nullable()->after('montant_ht');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['fournisseur_nom', 'montant_ht', 'montant_ttc']);
        });
    }
};
