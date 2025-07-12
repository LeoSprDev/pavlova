@component('mail::message')
# Rapport Hebdomadaire

Bonjour {{ $userName }},

Vous trouverez ci-joint le résumé des demandes et budgets de la semaine.

Merci,
{{ config('app.name') }}
@endcomponent
