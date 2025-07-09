<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            $table->date('date_envoi_demande_fournisseur')->nullable();
            $table->date('date_reception_devis')->nullable();
            $table->decimal('prix_fournisseur_final', 10, 2)->nullable();
            $table->boolean('devis_fournisseur_valide')->default(false);
            $table->string('numero_commande_fournisseur')->nullable();
            $table->enum('statut_fournisseur', ['attente_devis', 'devis_recu', 'commande_passee'])->default('attente_devis');
        });
    }

    public function down(): void
    {
        Schema::table('demande_devis', function (Blueprint $table) {
            $table->dropColumn([
                'date_envoi_demande_fournisseur',
                'date_reception_devis',
                'prix_fournisseur_final',
                'devis_fournisseur_valide',
                'numero_commande_fournisseur',
                'statut_fournisseur',
            ]);
        });
    }
};
