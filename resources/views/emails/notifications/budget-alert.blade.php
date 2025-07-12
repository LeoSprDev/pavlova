@component('mail::message')
# 🚨 Alerte Budget Critique

## Budget {{ $budgetLigne->intitule }}
- **Service :** {{ $budgetLigne->service->nom }}
- **Utilisation :** {{ round($budgetLigne->getTauxUtilisation(), 1) }}%
- **Montant restant :** {{ number_format($budgetLigne->calculateBudgetRestant(), 2) }}€

@if($budgetLigne->getTauxUtilisation() > 95)
@component('mail::panel')
⚠️ **ATTENTION CRITIQUE** : Budget presque épuisé ! Action immédiate requise.
@endcomponent
@endif

@component('mail::button', ['url' => config('app.url') . '/admin/budget-lignes/' . $budgetLigne->id, 'color' => 'primary'])
Gérer ce Budget
@endcomponent

@component('mail::subcopy')
Cette alerte a été générée automatiquement. Seuil d'alerte {{ $seuil }}%.
@endcomponent
@endcomponent
