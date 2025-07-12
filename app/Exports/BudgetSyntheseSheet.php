<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\BudgetLigne;

class BudgetSyntheseSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(public ?int $serviceId = null)
    {
    }

    public function collection()
    {
        $query = BudgetLigne::query();
        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
        }

        return $query->get([
            'intitule',
            'montant_ht_prevu',
            'montant_ttc_prevu',
        ]);
    }

    public function headings(): array
    {
        return ['Intitulé', 'Budget HT', 'Budget TTC'];
    }

    public function title(): string
    {
        return 'Synthese';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
