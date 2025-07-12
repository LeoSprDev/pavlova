<?php
namespace App\Services;

use App\Models\{BudgetLigne, Service, DemandeDevis, User};
use Barryvdh\DomPDF\Facade\Pdf;

class ExecutivePDFExport
{
    public function generate(User $user, array $options): string
    {
        $data = $this->prepareData($user, $options);

        $pdf = Pdf::loadView('exports.pdf.executive-report', [
            'user' => $user,
            'data' => $data,
            'options' => $options,
            'generated_at' => now(),
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);
        return $pdf->output();
    }

    private function prepareData(User $user, array $options): array
    {
        $services = Service::with('budgetLignes')->get();
        return [
            'resume_executif' => 'Résumé automatique ...',
            'kpis_principaux' => [
                ['value' => number_format(BudgetLigne::sum('montant_ht_prevu'),2).' €', 'label' => 'Budget Alloué'],
                ['value' => number_format(BudgetLigne::sum('montant_depense_reel'),2).' €', 'label' => 'Dépensé'],
            ],
            'performance_services' => $services->map(function($s){
                $alloue = $s->budgetLignes->sum('montant_ht_prevu');
                $cons = $s->budgetLignes->sum('montant_depense_reel');
                $disp = $alloue - $cons;
                $taux = $alloue > 0 ? ($cons/$alloue)*100 : 0;
                return [
                    'nom' => $s->nom,
                    'budget_alloue' => $alloue,
                    'budget_consomme' => $cons,
                    'budget_disponible' => $disp,
                    'taux_utilisation' => $taux,
                    'status_class' => $taux > 90 ? 'danger' : ($taux > 75 ? 'warning' : 'success'),
                    'status_text' => $taux > 90 ? 'Critique' : ($taux > 75 ? 'Attention' : 'Ok'),
                ];
            })->toArray(),
            'alertes_critiques' => [],
            'recommandations' => [
                'Optimiser le suivi des engagements',
                'Analyser les fournisseurs stratégiques',
            ],
        ];
    }
}
