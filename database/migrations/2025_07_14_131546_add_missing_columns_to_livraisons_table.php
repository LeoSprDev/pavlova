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
        Schema::table('livraisons', function (Blueprint $table) {
            $table->date('date_livraison_reelle')->nullable()->comment('Date effective de livraison');
            $table->string('bons_livraison')->nullable()->comment('Fichiers de bons de livraison');
            $table->json('photos_reception')->nullable()->comment('Photos de réception');
            $table->text('anomalies')->nullable()->comment('Anomalies constatées');
            $table->text('actions_correctives')->nullable()->comment('Actions correctives prises');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->dropColumn(['date_livraison_reelle', 'bons_livraison', 'photos_reception', 'anomalies', 'actions_correctives']);
        });
    }
};
