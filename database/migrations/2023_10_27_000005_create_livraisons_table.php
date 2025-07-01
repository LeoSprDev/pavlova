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
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->date('date_livraison');
            $table->string('statut_reception'); // recue, en_attente, probleme_signalé, refusee
            $table->text('commentaire_reception')->nullable();
            $table->foreignId('verifie_par')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('conforme')->default(false);
            $table->text('actions_requises')->nullable();
            $table->boolean('litige_en_cours')->default(false);
            $table->integer('note_qualite')->nullable(); // e.g., 1 to 5
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
