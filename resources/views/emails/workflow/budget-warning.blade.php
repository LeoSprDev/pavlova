@component('mail::message')
# ⚠️ Dépassement Budget

Bonjour {{ $userName }},

La ligne budgétaire **{{ $budgetIntitule }}** a dépassé le budget prévu.

Montant dépassé : **{{ number_format($montantDepassement, 2) }}€** / Budget total : **{{ number_format($budgetTotal, 2) }}€**.

@component('mail::button', ['url' => $actionUrl])
Voir la ligne budgétaire
@endcomponent

{{ $warningMessage }}
@endcomponent
