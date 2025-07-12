<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\DemandeDevis;

class DemandesDevisExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    protected $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'ID', 'Dénomination', 'Service', 'Statut', 'Montant TTC (€)', 'Créé le'
        ];
    }

    public function map($record): array
    {
        /** @var DemandeDevis $record */
        return [
            $record->id,
            $record->denomination,
            optional($record->serviceDemandeur)->nom,
            $record->statut,
            number_format($record->prix_total_ttc, 2),
            $record->created_at->format('d/m/Y'),
        ];
    }
}
