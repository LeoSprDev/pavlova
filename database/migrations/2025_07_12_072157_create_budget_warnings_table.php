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
        Schema::create('budget_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_ligne_id')->constrained('budget_lignes')->cascadeOnDelete();
            $table->foreignId('demande_devis_id')->nullable()->constrained('demande_devis')->cascadeOnDelete();
            $table->decimal('montant_engage', 15, 2);
            $table->decimal('montant_depassement', 15, 2)->default(0);
            $table->string('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_warnings');
    }
};
