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
        Schema::create('demande_devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_demandeur_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('budget_ligne_id')->constrained('budget_lignes')->onDelete('cascade');
            $table->string('denomination');
            $table->string('reference_produit')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantite');
            $table->decimal('prix_unitaire_ht', 15, 2);
            $table->decimal('prix_total_ttc', 15, 2);
            $table->string('fournisseur_propose')->nullable();
            $table->text('justification_besoin');
            $table->string('urgence')->default('normale'); // normale, urgente, critique
            $table->date('date_besoin');
            $table->string('statut')->default('pending'); // pending, approved_budget, approved_achat, delivered, rejected, cancelled
            $table->text('commentaire_validation')->nullable();
            $table->timestamp('date_validation_budget')->nullable();
            $table->timestamp('date_validation_achat')->nullable();
            // ringlesoft/laravel-process-approval fields will be added by its own migration if needed
            // or we add them manually if we know them: 'current_step', 'approved_steps', 'next_step', etc.
            // For now, assuming the package handles its own columns or they are defined in the model trait.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_devis');
    }
};
