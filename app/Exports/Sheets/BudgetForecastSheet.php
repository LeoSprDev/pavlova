<?php
namespace App\Exports\Sheets;

use App\Models\BudgetLigne;
use Maatwebsite\Excel\Concerns\{FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetForecastSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $dateDebut;
    protected $dateFin;
    protected array $serviceIds;

    public function __construct($dateDebut, $dateFin, array $serviceIds)
    {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->serviceIds = $serviceIds;
    }

    public function collection()
    {
        $data = BudgetLigne::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->when($this->serviceIds, fn($q) => $q->whereIn('service_id', $this->serviceIds))
            ->selectRaw('service_id, SUM(montant_ht_prevu) as total')
            ->groupBy('service_id')
            ->get();

        return $data->map(fn($row) => [
            'Service' => optional($row->service)->nom,
            'Budget Prévu' => $row->total,
            'Prévision Prochaine Année' => round($row->total * 1.05, 2),
        ]);
    }

    public function headings(): array
    {
        return ['Service', 'Budget Prévu', 'Prévision Prochaine Année'];
    }

    public function title(): string
    {
        return 'Prévisions';
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
