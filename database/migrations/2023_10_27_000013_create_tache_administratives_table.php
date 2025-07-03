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
        Schema::create('tache_administratives', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->string('priorite')->default('moyenne'); // basse, moyenne, haute, critique
            $table->foreignId('assigne_a')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('budget_ligne_id')->nullable()->constrained('budget_lignes')->onDelete('set null');
            $table->foreignId('demande_devis_id')->nullable()->constrained('demande_devis')->onDelete('set null');
            $table->string('statut')->default('nouveau'); // nouveau, en_cours, terminee, annulee
            $table->timestamp('date_echeance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tache_administratives');
    }
};
