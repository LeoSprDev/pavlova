<?php
namespace App\Exports\Sheets;

use App\Models\BudgetLigne;
use Maatwebsite\Excel\Concerns\{FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TendancesSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
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

    public function array(): array
    {
        $data = BudgetLigne::whereDate('created_at', '>=', $this->dateDebut)
            ->whereDate('created_at', '<=', $this->dateFin)
            ->when(!empty($this->serviceIds), fn($q) => $q->whereIn('service_id', $this->serviceIds))
            ->selectRaw('YEAR(created_at) as annee, MONTH(created_at) as mois, SUM(montant_ht_prevu) as total')
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();
        $rows = $data->map(fn($d) => [
            $d->annee . '-' . str_pad($d->mois,2,'0',STR_PAD_LEFT),
            $d->total
        ])->toArray();
        return $rows;
    }

    public function headings(): array
    {
        return ['Période', 'Budget Total'];
    }

    public function title(): string
    {
        return 'Tendances';
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
