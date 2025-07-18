<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\{DemandeDevis, Commande};
use Illuminate\Support\Facades\DB;

class FournisseurPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Performance Fournisseurs';
    protected static ?int $sort = 4;
    
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'service-achat', 'responsable-direction', 'responsable-service'
        ]) ?? false;
    }
    
    protected function getData(): array
    {
        $fournisseurs = $this->getTopFournisseurs();
        
        return [
            'datasets' => [
                [
                    'label' => 'Délai Moyen (jours)',
                    'data' => $fournisseurs->pluck('delai_moyen')->toArray(),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Nombre Commandes',
                    'data' => $fournisseurs->pluck('nb_commandes')->toArray(),
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#2563EB',
                    'borderWidth' => 1,
                ]
            ],
            'labels' => $fournisseurs->pluck('nom')->toArray(),
        ];
    }
    
    private function getTopFournisseurs()
    {
        return DB::table('demande_devis')
            ->join('commandes', 'demande_devis.id', '=', 'commandes.demande_devis_id')
            ->select(
                'demande_devis.fournisseur_propose as nom',
                DB::raw('COUNT(*) as nb_commandes'),
                DB::raw('AVG(CAST(julianday(commandes.updated_at) - julianday(commandes.date_commande) AS INTEGER)) as delai_moyen'),
                DB::raw('AVG(demande_devis.prix_total_ttc) as montant_moyen')
            )
            ->where('demande_devis.statut', 'delivered_confirmed')
            ->whereNotNull('demande_devis.fournisseur_propose')
            ->groupBy('demande_devis.fournisseur_propose')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('nb_commandes')
            ->limit(8)
            ->get()
            ->map(function($item) {
                return [
                    'nom' => $item->nom,
                    'nb_commandes' => $item->nb_commandes,
                    'delai_moyen' => round($item->delai_moyen ?? 0, 1),
                    'montant_moyen' => round($item->montant_moyen, 2)
                ];
            });
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y;
                                if (context.dataset.label === "Délai Moyen (jours)") {
                                    label += " jours";
                                }
                            }
                            return label;
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}