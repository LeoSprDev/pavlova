<?php
namespace App\Exports;

use App\Models\Service;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeWriting;
use App\Exports\Sheets\SyntheseExecutiveSheet;
use App\Exports\Sheets\ServiceDetailSheet;
use App\Exports\Sheets\WorkflowHistorySheet;
use App\Exports\Sheets\FournisseurAnalysisSheet;
use App\Exports\Sheets\TendancesSheet;
use App\Exports\Sheets\BudgetForecastSheet;

class BudgetCompletExport implements WithMultipleSheets, WithTitle, WithEvents
{
    protected User $user;
    protected array $options;
    protected $dateDebut;
    protected $dateFin;
    protected array $serviceIds = [];

    public function __construct(User $user, array $options = [])
    {
        $this->user = $user;
        $this->options = array_merge([
            'inclure_graphiques' => true,
            'inclure_workflow' => true,
            'inclure_fournisseurs' => true,
            'inclure_tendances' => true,
            'format_executif' => false,
            'services' => [],
            'periode' => 'annee_courante'
        ], $options);
        $this->configurePeriode();
        $this->configureServices();
    }

    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new SyntheseExecutiveSheet(
            $this->user,
            $this->dateDebut,
            $this->dateFin,
            $this->options
        );

        foreach ($this->serviceIds as $serviceId) {
            $service = Service::find($serviceId);
            if ($service) {
                $sheets[] = new ServiceDetailSheet($service, $this->dateDebut, $this->dateFin, $this->options);
            }
        }

        if ($this->options['inclure_workflow']) {
            $sheets[] = new WorkflowHistorySheet($this->dateDebut, $this->dateFin, $this->serviceIds, $this->options);
        }

        if ($this->options['inclure_fournisseurs']) {
            $sheets[] = new FournisseurAnalysisSheet($this->dateDebut, $this->dateFin, $this->options);
        }

        if ($this->options['inclure_tendances']) {
            $sheets[] = new TendancesSheet($this->dateDebut, $this->dateFin, $this->serviceIds, $this->options);
            $sheets[] = new BudgetForecastSheet($this->dateDebut, $this->dateFin, $this->serviceIds);
        }

        return $sheets;
    }

    public function title(): string
    {
        $suffix = $this->options['format_executif'] ? '_EXECUTIF' : '_COMPLET';
        return 'BUDGET_PAVLOVA_' . now()->format('Y-m-d') . $suffix;
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()
                    ->setCreator('Pavlova Budget Workflow')
                    ->setTitle('Rapport Budget Complet')
                    ->setDescription('Export revolutionnaire multi-feuilles')
                    ->setCompany('Pavlova Organization')
                    ->setManager($this->user->name);
            },
            BeforeWriting::class => function(BeforeWriting $event) {
                $event->writer->getDelegate()->getActiveSheet()
                    ->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
            }
        ];
    }

    private function configurePeriode(): void
    {
        switch ($this->options['periode']) {
            case 'mois_courant':
                $this->dateDebut = now()->startOfMonth();
                $this->dateFin = now()->endOfMonth();
                break;
            case 'trimestre_courant':
                $this->dateDebut = now()->startOfQuarter();
                $this->dateFin = now()->endOfQuarter();
                break;
            case 'personnalise':
                $this->dateDebut = $this->options['date_debut'] ?? now()->startOfYear();
                $this->dateFin = $this->options['date_fin'] ?? now()->endOfYear();
                break;
            case 'annee_courante':
            default:
                $this->dateDebut = now()->startOfYear();
                $this->dateFin = now()->endOfYear();
                break;
        }
    }

    private function configureServices(): void
    {
        if (!empty($this->options['services'])) {
            $this->serviceIds = $this->options['services'];
        } elseif ($this->user->service_id) {
            $this->serviceIds = [$this->user->service_id];
        } else {
            $this->serviceIds = Service::pluck('id')->toArray();
        }
    }
}
