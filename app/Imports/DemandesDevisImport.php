<?php
namespace App\Imports;

use App\Models\DemandeDevis;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DemandesDevisImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new DemandeDevis([
            'denomination' => $row['denomination'] ?? 'Demande',
            'service_demandeur_id' => $row['service_id'] ?? null,
            'budget_ligne_id' => $row['budget_ligne_id'] ?? null,
            'prix_total_ttc' => $row['prix_total_ttc'] ?? 0,
            'statut' => $row['statut'] ?? 'pending_manager',
        ]);
    }
}
