@component('mail::message')
# ❌ Demande Rejetée

Bonjour {{ $userName }},

Votre demande **#{{ $demandeId }} - {{ $denomination }}** a été rejetée.

**Raison :** {{ $reason }}

@component('mail::button', ['url' => $actionUrl])
Voir la demande
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent
