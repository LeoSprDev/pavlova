<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\BudgetLigne;
use App\Models\DemandeDevis;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BudgetDashboard extends Component
{
    public ?int $serviceId = null;

    public function mount()
    {
        if (auth()->user()->hasRole('service-demandeur')) {
            $this->serviceId = auth()->user()->service_id;
        }
    }

    public function getStats(): array
    {
        if ($this->serviceId) {
            return $this->getServiceStats();
        }
        return $this->getGlobalStats();
    }

    private function getServiceStats(): array
    {
        $budgetTotal = BudgetLigne::where('service_id', $this->serviceId)
            ->where('valide_budget', 'oui')
            ->sum('montant_ht_prevu');

        $budgetConsomme = DemandeDevis::where('service_demandeur_id', $this->serviceId)
            ->where('statut', 'delivered')
            ->sum('prix_total_ttc');

        $budgetDisponible = $budgetTotal - $budgetConsomme;
        $tauxConsommation = $budgetTotal > 0 ? ($budgetConsomme / $budgetTotal) * 100 : 0;

        $demandesEnCours = DemandeDevis::where('service_demandeur_id', $this->serviceId)
            ->whereIn('statut', ['pending', 'approved_budget'])
            ->count();

        return [
            Stat::make('Budget Disponible', number_format($budgetDisponible, 2) . ' €')
                ->description('Sur ' . number_format($budgetTotal, 2) . ' € alloués')
                ->color($budgetDisponible > 1000 ? 'success' : ($budgetDisponible > 0 ? 'warning' : 'danger'))
                ->chart($this->getConsommationChart()),

            Stat::make('Demandes en Cours', $demandesEnCours)
                ->description('En attente de validation')
                ->color($demandesEnCours > 5 ? 'warning' : 'info'),

            Stat::make('Taux Consommation', round($tauxConsommation, 1) . '%')
                ->description($tauxConsommation > 90 ? 'Budget presque épuisé!' : 'Consommation normale')
                ->color($tauxConsommation > 90 ? 'danger' : ($tauxConsommation > 70 ? 'warning' : 'success'))
        ];
    }

    private function getGlobalStats(): array
    {
        $budgetTotalOrg = BudgetLigne::where('valide_budget', 'oui')->sum('montant_ht_prevu');
        $budgetConsommeTotal = DemandeDevis::where('statut', 'delivered')->sum('prix_total_ttc');
        $demandesAValider = DemandeDevis::where('statut', 'pending')->count();
        $depassements = BudgetLigne::whereRaw('montant_depense_reel > montant_ht_prevu')->count();

        return [
            Stat::make('Budget Total Organisation', number_format($budgetTotalOrg, 2) . ' €')
                ->description('Tous services confondus')
                ->color('primary'),

            Stat::make('Demandes à Valider', $demandesAValider)
                ->description('En attente validation budget')
                ->color($demandesAValider > 10 ? 'danger' : 'success'),

            Stat::make('Alertes Dépassement', $depassements)
                ->description('Lignes budget en dépassement')
                ->color($depassements > 0 ? 'danger' : 'success')
        ];
    }

    private function getConsommationChart(): array
    {
        // Graphique consommation 6 derniers mois
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $consommation = DemandeDevis::when($this->serviceId, fn($q) => $q->where('service_demandeur_id', $this->serviceId))
                ->where('statut', 'delivered')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('prix_total_ttc');
            $data[] = $consommation;
        }
        return $data;
    }

    public function render()
    {
        return view('livewire.budget-dashboard', [
            'stats' => $this->getStats()
        ]);
    }
}
