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
        Schema::create('budget_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_ligne_id')->constrained('budget_lignes')->cascadeOnDelete();
            $table->foreignId('demande_devis_id')->constrained('demande_devis')->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->timestamp('date_engagement')->nullable();
            $table->timestamp('date_degagement')->nullable();
            $table->string('statut')->default('engage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_engagements');
    }
};
