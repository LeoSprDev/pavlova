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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_devis_id')->constrained('demande_devis')->onDelete('cascade');
            $table->string('numero_commande')->unique();
            $table->date('date_commande');
            $table->string('commanditaire'); // Service Achat, Responsable Service, etc.
            $table->string('statut')->default('en_cours'); // en_cours, livree_partiellement, livree, annulee
            $table->date('date_livraison_prevue');
            $table->decimal('montant_reel', 15, 2);
            $table->string('fournisseur_contact')->nullable();
            $table->string('fournisseur_email')->nullable();
            $table->string('conditions_paiement')->nullable();
            $table->string('delai_livraison')->nullable();
            $table->integer('nb_relances')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
