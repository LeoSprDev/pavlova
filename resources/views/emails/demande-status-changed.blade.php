@component('mail::message')
# Notification de demande

La demande **{{ $demande->denomination }}** est maintenant **{{ $demande->statut }}**.

@component('mail::button', ['url' => url('/')])
Voir la demande
@endcomponent

Merci.
@endcomponent
