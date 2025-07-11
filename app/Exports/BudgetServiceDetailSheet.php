<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\BudgetLigne;

class BudgetServiceDetailSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(public ?int $serviceId = null)
    {
    }

    public function collection()
    {
        $query = BudgetLigne::with('service');
        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
        }

        return $query->get()->map(function ($ligne) {
            return [
                'service' => $ligne->service->nom,
                'intitule' => $ligne->intitule,
                'montant' => $ligne->montant_ht_prevu,
            ];
        });
    }

    public function headings(): array
    {
        return ['Service', 'Intitulé', 'Montant HT'];
    }

    public function title(): string
    {
        return 'Details';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
