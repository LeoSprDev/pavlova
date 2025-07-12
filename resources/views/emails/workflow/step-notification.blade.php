@component('mail::message')
# Bonjour {{ $userName }}

La demande **#{{ $demandeId }} - {{ $demandeDenomination }}** nécessite votre action : **{{ $action }}**.

@component('mail::button', ['url' => $actionUrl])
Voir la demande
@endcomponent

Date limite : {{ \Carbon\Carbon::parse($deadlineDate)->format('d/m/Y') }}

Merci,
{{ config('app.name') }}
@endcomponent
