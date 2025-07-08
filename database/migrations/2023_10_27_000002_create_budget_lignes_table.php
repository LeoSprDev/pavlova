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
        Schema::create('budget_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->date('date_prevue');
            $table->string('intitule');
            $table->string('nature'); // abonnement, materiel, infrastructure, service
            $table->string('fournisseur_prevu')->nullable();
            $table->string('base_calcul'); // estimation, prix_ferme
            $table->integer('quantite')->default(1);
            $table->decimal('montant_ht_prevu', 15, 2);
            $table->decimal('montant_ttc_prevu', 15, 2);
            $table->decimal('montant_depense_reel', 15, 2)->default(0);
            $table->string('categorie'); // software, hardware, mobilier, service
            $table->string('type_depense'); // CAPEX, OPEX
            $table->text('commentaire_service')->nullable();
            $table->text('commentaire_budget')->nullable();
            $table->string('valide_budget')->default('non'); // oui, non, potentiellement
            $table->boolean('depassement')->virtualAs('montant_depense_reel > montant_ht_prevu')->nullable(); // Calculated
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_lignes');
    }
};
