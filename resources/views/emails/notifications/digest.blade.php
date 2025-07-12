@component('mail::message')
# Bonjour {{ $user->name }}

Voici un résumé de vos dernières notifications :

@foreach($notifications as $notification)
- {{ $notification->data['title'] ?? 'Notification' }}
@endforeach

@component('mail::button', ['url' => config('app.url')])
Ouvrir l'application
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent
