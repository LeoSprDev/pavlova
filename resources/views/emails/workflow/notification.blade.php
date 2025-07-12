@component('mail::message')
# Nouvelle demande à approuver

La demande **#{{ $demande->id }}** requiert votre validation.

@component('mail::button', ['url' => url('/admin/demande-devis/'.$demande->id)])
Voir la demande
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent
