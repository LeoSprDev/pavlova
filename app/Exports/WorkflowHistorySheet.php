<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\DemandeDevis;

class WorkflowHistorySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(public ?int $serviceId = null)
    {
    }

    public function collection()
    {
        $query = DemandeDevis::with('serviceDemandeur');
        if ($this->serviceId) {
            $query->where('service_demandeur_id', $this->serviceId);
        }

        return $query->get()->map(function ($demande) {
            return [
                'service' => $demande->serviceDemandeur->nom,
                'denomination' => $demande->denomination,
                'statut' => $demande->statut,
            ];
        });
    }

    public function headings(): array
    {
        return ['Service', 'Demande', 'Statut'];
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
