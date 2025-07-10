<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\DemandeDevis;

class AgentDashboard extends Widget
{
    protected static string $view = 'filament.widgets.agent-dashboard';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('agent-service') ?? false;
    }

    public function getViewData(): array
    {
        $user = Auth::user();

        return [
            'mesDemandesEnAttente' => DemandeDevis::where('created_by', $user->id)->whereIn('statut', ['pending', 'approved_service', 'approved_budget'])->count(),
            'demandesApprouvees' => DemandeDevis::where('created_by', $user->id)->where('statut', 'approved_final')->count(),
            'budgetDisponible' => optional($user->service)->budgetLignes()->sum('montant_ht_prevu') - optional($user->service)->budgetLignes()->sum('montant_depense_reel'),
            'dernieresDemandes' => DemandeDevis::where('created_by', $user->id)->latest()->limit(5)->get(),
        ];
    }
}
