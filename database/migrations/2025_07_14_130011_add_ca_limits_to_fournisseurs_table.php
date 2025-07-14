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
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->decimal('ca_limite_annuel', 10, 2)->nullable()->comment('Limite CA annuel autorisé');
            $table->decimal('ca_limite_mensuel', 10, 2)->nullable()->comment('Limite CA mensuel autorisé');
            $table->boolean('limite_active')->default(false)->comment('Indique si les limites sont activées');
            $table->text('note_limite')->nullable()->comment('Note explicative sur les limites');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['ca_limite_annuel', 'ca_limite_mensuel', 'limite_active', 'note_limite']);
        });
    }
};
