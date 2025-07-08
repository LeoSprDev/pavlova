<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;
use App\Models\Commande;
use App\Models\Service;

class DemoDataCompletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CRÉER SCÉNARIOS DE TEST COMPLETS
        $services = Service::all();
        
        foreach ($services as $service) {
            // Créer plusieurs lignes budgétaires par service
            $ligneNormale = BudgetLigne::create([
                'service_id' => $service->id,
                'intitule' => "Budget Normal {$service->nom}",
                'montant_ht_prevu' => 10000,
                'montant_ttc_prevu' => 12000,
                'valide_budget' => 'oui',
                'date_prevue' => now()->addMonths(6),
                'nature' => 'Equipement',
                'base_calcul' => 'Forfait',
                'categorie' => 'Informatique',
                'type_depense' => 'Investissement',
                'commentaire_service' => "Budget normal pour les besoins du service {$service->nom}",
            ]);
            
            // Scénario 1: Budget dépassé (pour tester alertes)
            $ligneDepassee = BudgetLigne::create([
                'service_id' => $service->id,
                'intitule' => "Budget Dépassé - Test Alerte {$service->nom}",
                'montant_ht_prevu' => 1000,
                'montant_ttc_prevu' => 1200,
                'valide_budget' => 'oui',
                'date_prevue' => now()->addMonths(3),
                'nature' => 'Test',
                'base_calcul' => 'Unitaire',
                'categorie' => 'Test',
                'type_depense' => 'Fonctionnement',
                'commentaire_service' => "Budget test pour vérifier les alertes de dépassement",
            ]);
            
            DemandeDevis::create([
                'budget_ligne_id' => $ligneDepassee->id,
                'service_demandeur_id' => $service->id,
                'denomination' => 'Demande Dépassement Test',
                'description' => 'Demande qui dépasse le budget pour tester les alertes',
                'quantite' => 1,
                'prix_unitaire_ht' => 1200,
                'prix_total_ttc' => 1440, // Dépasse le budget
                'statut' => 'delivered',
                'date_besoin' => now()->addDays(30),
                'justification_besoin' => 'Test de dépassement budgétaire',
                'urgence' => 'normale',
            ]);
            
            // Scénario 2: Workflow complet en cours
            $ligneWorkflow = BudgetLigne::create([
                'service_id' => $service->id,
                'intitule' => "Workflow En Cours - Test {$service->nom}",
                'montant_ht_prevu' => 5000,
                'montant_ttc_prevu' => 6000,
                'valide_budget' => 'oui',
                'date_prevue' => now()->addMonths(4),
                'nature' => 'Equipement',
                'base_calcul' => 'Unitaire',
                'categorie' => 'Informatique',
                'type_depense' => 'Investissement',
                'commentaire_service' => "Budget pour tester le workflow complet",
            ]);
            
            $demandeWorkflow = DemandeDevis::create([
                'budget_ligne_id' => $ligneWorkflow->id,
                'service_demandeur_id' => $service->id,
                'denomination' => 'MacBook Pro M3',
                'description' => 'Ordinateur portable pour développement',
                'quantite' => 1,
                'prix_unitaire_ht' => 2500,
                'prix_total_ttc' => 3000,
                'statut' => 'pending',
                'current_step' => 'responsable-budget',
                'date_besoin' => now()->addDays(45),
                'justification_besoin' => 'Remplacement ordinateur obsolète pour améliorer productivité',
                'urgence' => 'normale',
                'fournisseur_propose' => 'Apple Store',
                'reference_produit' => 'MBP-M3-14-512',
            ]);
            
            // Scénario 3: Demande approuvée budget
            $demandeApprouvee = DemandeDevis::create([
                'budget_ligne_id' => $ligneNormale->id,
                'service_demandeur_id' => $service->id,
                'denomination' => 'Licences Office 365',
                'description' => 'Licences Microsoft Office pour équipe',
                'quantite' => 10,
                'prix_unitaire_ht' => 120,
                'prix_total_ttc' => 1440,
                'statut' => 'approved_budget',
                'current_step' => 'service-achat',
                'date_besoin' => now()->addDays(15),
                'justification_besoin' => 'Licences expirées, renouvellement nécessaire',
                'urgence' => 'urgente',
                'fournisseur_propose' => 'Microsoft',
                'reference_produit' => 'O365-BUSINESS-PREMIUM',
            ]);
            
            // Scénario 4: Commande en cours
            if ($service->id === 1) { // Seulement pour le premier service
                $commandeEnCours = Commande::create([
                    'demande_devis_id' => $demandeApprouvee->id,
                    'numero_commande' => 'CMD-2025-001',
                    'date_commande' => now()->subDays(10),
                    'date_livraison_prevue' => now()->addDays(5),
                    'statut' => 'en_cours',
                    'commanditaire' => 'Service Achat',
                    'fournisseur_contact' => 'Microsoft France',
                    'fournisseur_email' => 'commandes@microsoft.fr',
                    'montant_reel' => 1440,
                    'conditions_paiement' => '30 jours',
                    'delai_livraison' => '15 jours',
                ]);
            }
        }
    }
}
