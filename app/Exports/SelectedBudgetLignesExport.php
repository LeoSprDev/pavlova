<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\BudgetLigne;

class SelectedBudgetLignesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
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
            'Service', 'Intitulé', 'Montant HT', 'Montant TTC', 'Validé', 'Restant'
        ];
    }

    public function map($record): array
    {
        /** @var BudgetLigne $record */
        $restant = $record->calculateBudgetRestant();
        return [
            optional($record->service)->nom,
            $record->intitule,
            number_format($record->montant_ht_prevu,2),
            number_format($record->montant_ttc_prevu,2),
            $record->valide_budget,
            number_format($restant,2),
        ];
    }
}
