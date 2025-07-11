<?php
namespace App\Exports\Sheets;

use App\Models\DemandeDevis;
use Maatwebsite\Excel\Concerns\{FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkflowHistorySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $dateDebut;
    protected $dateFin;
    protected array $serviceIds;
    protected array $options;

    public function __construct($dateDebut, $dateFin, array $serviceIds, array $options)
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->serviceIds = $serviceIds;
        $this->options = $options;
    }

    public function collection()
    {
        return DemandeDevis::with('serviceDemandeur')
            ->whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->when(!empty($this->serviceIds), fn($q) => $q->whereIn('service_demandeur_id', $this->serviceIds))
            ->get()
            ->map(fn($d) => [
                'Service' => $d->serviceDemandeur?->nom,
                'Demande' => $d->denomination,
                'Statut' => $d->statut,
                'Créée le' => $d->created_at->format('d/m/Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Service', 'Demande', 'Statut', 'Créée le'];
    }

    public function title(): string
    {
        return 'Workflow';
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
