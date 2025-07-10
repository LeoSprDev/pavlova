<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->timestamp('date_validation_finale')->nullable();
            $table->boolean('workflow_complete')->default(false);
            $table->unsignedBigInteger('finalise_par')->nullable();
            $table->text('commentaire_finalisation')->nullable();
            
            $table->foreign('finalise_par')->references('id')->on('users');
            $table->index(['workflow_complete', 'date_validation_finale']);
        });
    }

    public function down(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->dropForeign(['finalise_par']);
            $table->dropColumn([
                'date_validation_finale',
                'workflow_complete',
                'finalise_par',
                'commentaire_finalisation'
            ]);
        });
    }
};
