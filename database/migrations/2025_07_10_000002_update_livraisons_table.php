<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->renameColumn('date_livraison', 'date_livraison_prevue');
            $table->date('date_livraison_reelle')->nullable();
            $table->text('anomalies')->nullable();
            $table->text('actions_correctives')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->renameColumn('date_livraison_prevue', 'date_livraison');
            $table->dropColumn(['date_livraison_reelle', 'anomalies', 'actions_correctives']);
        });
    }
};
