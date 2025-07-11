<?php
namespace App\Exports\Sheets;

use App\Models\BudgetLigne;
use App\Models\Service;
use Maatwebsite\Excel\Concerns\{FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceDetailSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected Service $service;
    protected $dateDebut;
    protected $dateFin;
    protected array $options;

    public function __construct(Service $service, $dateDebut, $dateFin, array $options)
    {
        $this->service = $service;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->options = $options;
    }

    public function collection()
    {
        return BudgetLigne::where('service_id', $this->service->id)
            ->whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->get()
            ->map(function($ligne){
                return [
                    'Intitulé' => $ligne->intitule,
                    'Prévu HT' => $ligne->montant_ht_prevu,
                    'Dépensé' => $ligne->montant_depense_reel,
                    'Engagé' => $ligne->montant_engage,
                ];
            });
    }

    public function headings(): array
    {
        return ['Intitulé', 'Budget HT', 'Dépensé', 'Engagé'];
    }

    public function title(): string
    {
        return 'Service - ' . $this->service->nom;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
