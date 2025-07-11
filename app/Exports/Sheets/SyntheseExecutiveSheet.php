<?php
namespace App\Exports\Sheets;

use App\Models\{BudgetLigne, DemandeDevis, Service};
use Maatwebsite\Excel\Concerns\{FromArray, WithTitle, WithHeadings, WithStyles, WithCharts, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Chart\{Chart, DataSeries, DataSeriesValues, Legend, PlotArea, Title};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

class SyntheseExecutiveSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithCharts, ShouldAutoSize
{
    protected $user;
    protected $dateDebut;
    protected $dateFin;
    protected $options;
    protected $data;

    public function __construct($user, $dateDebut, $dateFin, array $options)
    {
        $this->user = $user;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->options = $options;
        $this->prepareData();
    }

    public function array(): array
    {
        return [
            ['MÉTRIQUES CLÉS', '', '', ''],
            ['Budget Total Alloué', number_format($this->data['budget_total'], 2) . ' €', '', ''],
            ['Budget Consommé', number_format($this->data['budget_consomme'], 2) . ' €', '', ''],
            ['Budget Engagé', number_format($this->data['budget_engage'], 2) . ' €', '', ''],
            ['Budget Disponible', number_format($this->data['budget_disponible'], 2) . ' €', '', ''],
            ['Taux Utilisation', round($this->data['taux_utilisation'], 1) . '%', '', ''],
            ['', '', '', ''],
            ['PERFORMANCE PAR SERVICE', '', '', ''],
            ['Service', 'Budget Alloué', 'Consommé', 'Taux %'],
            ...$this->data['services_performance'],
            ['', '', '', ''],
            ['MÉTRIQUES WORKFLOW', '', '', ''],
            ['Demandes Totales', $this->data['demandes_total'], '', ''],
            ['Demandes Approuvées', $this->data['demandes_approuvees'], '', ''],
            ['Demandes en Cours', $this->data['demandes_en_cours'], '', ''],
            ['Délai Moyen Approbation', $this->data['delai_moyen'] . ' jours', '', ''],
            ['', '', '', ''],
            ['TOP FOURNISSEURS', '', '', ''],
            ['Fournisseur', 'Montant', 'Nb Commandes', 'Délai Moyen'],
            ...$this->data['top_fournisseurs'],
            ['', '', '', ''],
            ['ALERTES & RECOMMANDATIONS', '', '', ''],
            ...$this->data['alertes']
        ];
    }

    public function headings(): array
    {
        return [
            ['RAPPORT EXÉCUTIF BUDGET - ' . now()->format('d/m/Y')],
            ['Période: ' . $this->dateDebut->format('d/m/Y') . ' - ' . $this->dateFin->format('d/m/Y')],
            ['Généré par: ' . $this->user->name],
            ['']
        ];
    }

    public function title(): string
    {
        return '📊 Synthèse Exécutive';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1f2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'A6:D6' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '3b82f6']],
            ],
            'A14:D14' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '059669']],
            ],
            'A22:D22' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dc2626']],
            ],
            'A1:D50' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]
        ];
    }

    public function charts(): array
    {
        if (!$this->options['inclure_graphiques']) {
            return [];
        }

        $dataSeriesLabels = [new DataSeriesValues('String', 'Synthèse Exécutive!$A$16:$A$' . (15 + count($this->data['services_performance'])))];
        $dataSeriesValues = [new DataSeriesValues('Number', 'Synthèse Exécutive!$C$16:$C$' . (15 + count($this->data['services_performance'])))];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            [],
            $dataSeriesValues
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Répartition Budget par Service');

        $chart = new Chart('budgetChart', $title, $legend, $plotArea);
        $chart->setTopLeftPosition('F6');
        $chart->setBottomRightPosition('M20');

        return [$chart];
    }

    private function prepareData(): void
    {
        $budgetTotal = BudgetLigne::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->where('valide_budget', 'oui')
            ->sum('montant_ht_prevu');
        $budgetConsomme = BudgetLigne::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->sum('montant_depense_reel');
        $budgetEngage = BudgetLigne::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->sum('montant_engage');
        $this->data = [
            'budget_total' => $budgetTotal,
            'budget_consomme' => $budgetConsomme,
            'budget_engage' => $budgetEngage,
            'budget_disponible' => $budgetTotal - $budgetConsomme - $budgetEngage,
            'taux_utilisation' => $budgetTotal > 0 ? (($budgetConsomme + $budgetEngage) / $budgetTotal) * 100 : 0,
            'services_performance' => $this->getServicesPerformance(),
            'demandes_total' => $this->getDemandesTotal(),
            'demandes_approuvees' => $this->getDemandesApprouvees(),
            'demandes_en_cours' => $this->getDemandesEnCours(),
            'delai_moyen' => $this->getDelaiMoyenApprobation(),
            'top_fournisseurs' => $this->getTopFournisseurs(),
            'alertes' => $this->generateAlertes()
        ];
    }

    private function getServicesPerformance(): array
    {
        $services = Service::with(['budgetLignes' => function($query) {
            $query->whereDate('created_at', '>=', $this->dateDebut)
                  ->whereDate('created_at', '<=', $this->dateFin);
        }])->get();
        $performance = [];
        foreach ($services as $service) {
            $alloue = $service->budgetLignes->sum('montant_ht_prevu');
            $consomme = $service->budgetLignes->sum('montant_depense_reel');
            $taux = $alloue > 0 ? ($consomme / $alloue) * 100 : 0;
            $performance[] = [
                $service->nom,
                number_format($alloue, 2) . ' €',
                number_format($consomme, 2) . ' €',
                round($taux, 1) . '%'
            ];
        }
        return $performance;
    }

    private function getTopFournisseurs(): array
    {
        return DemandeDevis::selectRaw('fournisseur_propose, SUM(prix_total_ttc) as total, COUNT(*) as nb_commandes, AVG(DATEDIFF(date_validation_achat, created_at)) as delai_moyen')
            ->whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->whereNotNull('fournisseur_propose')
            ->groupBy('fournisseur_propose')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                $item->fournisseur_propose,
                number_format($item->total, 2) . ' €',
                $item->nb_commandes,
                round($item->delai_moyen, 1) . ' jours'
            ])->toArray();
    }

    private function generateAlertes(): array
    {
        $alertes = [];
        if ($this->data['taux_utilisation'] > 90) {
            $alertes[] = ['🚨 CRITIQUE', 'Taux utilisation budget > 90%', '', ''];
        }
        if ($this->data['delai_moyen'] > 7) {
            $alertes[] = ['⚠️ ATTENTION', 'Délai approbation élevé (' . $this->data['delai_moyen'] . ' jours)', '', ''];
        }
        $alertes[] = ['💡 RECOMMANDATION', 'Optimiser workflow approbation', '', ''];
        $alertes[] = ['📈 AMÉLIORATION', 'Négocier meilleurs délais fournisseurs', '', ''];
        return $alertes;
    }

    private function getDemandesTotal(): int
    {
        return DemandeDevis::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->count();
    }

    private function getDemandesApprouvees(): int
    {
        return DemandeDevis::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->where('statut', 'delivered_confirmed')
            ->count();
    }

    private function getDemandesEnCours(): int
    {
        return DemandeDevis::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->whereIn('statut', ['pending_manager', 'pending_direction', 'pending_achat'])
            ->count();
    }

    private function getDelaiMoyenApprobation(): float
    {
        return DemandeDevis::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->whereNotNull('date_validation_achat')
            ->selectRaw('AVG(DATEDIFF(date_validation_achat, created_at)) as delai')
            ->value('delai') ?? 0;
    }
}
