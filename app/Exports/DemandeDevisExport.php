<?php

namespace App\Exports;

use App\Models\DemandeDevis;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DemandeDevisExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DemandeDevis::with([
            'serviceDemandeur', 
            'creator', 
            'approvalsHistory',
            'budgetLigne'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Service Demandeur',
            'Agent Demandeur',
            'Responsable Service',
            'Date Validation Service',
            'Dénomination',
            'Montant TTC',
            'Statut',
            'Date Validation Budget',
            'Date Validation Achat',
            'Créé le'
        ];
    }

    public function map($demande): array
    {
        $validationService = $demande->approvalsHistory()
            ->where('step', 'validation-responsable-service')
            ->where('status', 'approved')
            ->first();

        return [
            $demande->id,
            $demande->serviceDemandeur->nom ?? 'N/A',
            $demande->creator->name ?? 'N/A',
            $validationService?->user->name ?? 'En attente',
            $validationService?->created_at?->format('d/m/Y H:i') ?? '',
            $demande->denomination,
            $demande->prix_total_ttc,
            $demande->statut,
            $demande->date_validation_budget?->format('d/m/Y H:i') ?? '',
            $demande->date_validation_achat?->format('d/m/Y H:i') ?? '',
            $demande->created_at->format('d/m/Y H:i')
        ];
    }
}