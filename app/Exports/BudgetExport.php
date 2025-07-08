<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\BudgetLigne;

class BudgetExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        public ?int $serviceId = null,
        public ?string $periode = null
    ) {}

    public function collection()
    {
        return BudgetLigne::when($this->serviceId, fn($q) => $q->where('service_id', $this->serviceId))
            ->with(['service', 'demandesAssociees'])
            ->get()
            ->map(function($ligne) {
                $depenseReelle = $ligne->demandesAssociees->where('statut', 'delivered')->sum('prix_total_ttc');
                $budgetRestant = $ligne->montant_ht_prevu - $depenseReelle;
                $tauxConsommation = $ligne->montant_ht_prevu > 0 ?
                    ($depenseReelle / $ligne->montant_ht_prevu) * 100 : 0;

                return [
                    'service' => $ligne->service->nom,
                    'date_prevue' => $ligne->date_prevue->format('d/m/Y'),
                    'intitule' => $ligne->intitule,
                    'nature' => ucfirst($ligne->nature),
                    'fournisseur_prevu' => $ligne->fournisseur_prevu,
                    'montant_ht_prevu' => $ligne->montant_ht_prevu,
                    'montant_ttc_prevu' => $ligne->montant_ttc_prevu,
                    'montant_depense' => $depenseReelle,
                    'budget_restant' => $budgetRestant,
                    'taux_consommation' => round($tauxConsommation, 1),
                    'statut_validation' => ucfirst($ligne->valide_budget),
                    'type_depense' => $ligne->type_depense,
                    'nb_demandes' => $ligne->demandesAssociees->count()
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Service', 'Date Prévue', 'Intitulé', 'Nature', 'Fournisseur Prévu',
            'Budget HT (€)', 'Budget TTC (€)', 'Dépensé (€)', 'Restant (€)',
            'Consommation (%)', 'Statut Validation', 'Type Dépense', 'Nb Demandes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:M' => ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]
        ];
    }
}
