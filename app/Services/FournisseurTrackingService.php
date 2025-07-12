<?php
namespace App\Services;

use App\Models\{Fournisseur, DemandeDevis, Service};
use Illuminate\Support\Collection;

class FournisseurTrackingService
{
    public function createOrUpdateFournisseur(string $nom, array $details = []): Fournisseur
    {
        return Fournisseur::updateOrCreate(
            ['nom' => $nom],
            array_merge($details, [
                'nom_commercial' => $details['nom_commercial'] ?? $nom,
                'last_used_at' => now(),
            ])
        );
    }

    public function getCAFournisseur(Fournisseur $fournisseur, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subYear();
        $endDate = $endDate ?? now();

        $demandes = DemandeDevis::where('fournisseur_propose', $fournisseur->nom)
            ->orWhere('fournisseur_propose', $fournisseur->nom_commercial)
            ->whereIn('statut', ['delivered_confirmed', 'ordered'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'ca_total' => $demandes->sum('prix_total_ttc'),
            'nb_commandes' => $demandes->count(),
            'ca_moyen' => $demandes->avg('prix_total_ttc') ?? 0,
            'derniere_commande' => $demandes->max('created_at'),
            'services_clients' => $demandes->pluck('service_demandeur_id')->unique()->count(),
        ];
    }

    public function getTopFournisseurs(int $limit = 10, ?\Carbon\Carbon $startDate = null): Collection
    {
        $startDate = $startDate ?? now()->subYear();

        return Fournisseur::withSum(['demandes as ca_total' => function($q) use ($startDate) {
            $q->whereIn('statut', ['delivered_confirmed', 'ordered'])
              ->where('created_at', '>=', $startDate);
        }], 'prix_total_ttc')
        ->withCount(['demandes as nb_commandes' => function($q) use ($startDate) {
            $q->whereIn('statut', ['delivered_confirmed', 'ordered'])
              ->where('created_at', '>=', $startDate);
        }])
        ->orderBy('ca_total', 'desc')
        ->limit($limit)
        ->get();
    }

    public function getCAByService(Service $service, ?\Carbon\Carbon $startDate = null): array
    {
        $startDate = $startDate ?? now()->subYear();

        return DemandeDevis::select('fournisseur_propose')
            ->selectRaw('SUM(prix_total_ttc) as ca_total')
            ->selectRaw('COUNT(*) as nb_commandes')
            ->where('service_demandeur_id', $service->id)
            ->whereIn('statut', ['delivered_confirmed', 'ordered'])
            ->where('created_at', '>=', $startDate)
            ->groupBy('fournisseur_propose')
            ->orderBy('ca_total', 'desc')
            ->get()
            ->toArray();
    }
}
