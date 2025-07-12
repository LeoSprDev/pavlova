@component('mail::message')
# ⏰ Rappel Demande

Bonjour {{ $userName }},

La demande **#{{ $demandeId }} - {{ $denomination }}** est toujours en attente.

Date limite : {{ \Carbon\Carbon::parse($deadlineDate)->format('d/m/Y') }}

@component('mail::button', ['url' => $actionUrl])
Accéder à la demande
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent
