@component('mail::message')
# ✅ Demande Finalisée

Bonjour {{ $userName }},

La demande **#{{ $demandeId }} - {{ $denomination }}** est terminée.

@component('mail::button', ['url' => $actionUrl])
Voir le détail
@endcomponent

Merci pour votre collaboration.
@endcomponent
