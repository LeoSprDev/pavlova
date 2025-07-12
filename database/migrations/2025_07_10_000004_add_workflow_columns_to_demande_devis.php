<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            $table->timestamp('date_finalisation')->nullable();
            $table->unsignedBigInteger('finalise_par')->nullable();
            $table->text('commentaire_finalisation')->nullable();

            $table->foreign('finalise_par')->references('id')->on('users');
            $table->index(['statut', 'date_finalisation']);
        });
    }

    public function down(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            $table->dropForeign(['finalise_par']);
            $table->dropColumn([
                'date_finalisation',
                'finalise_par',
                'commentaire_finalisation'
            ]);
        });
    }
};
