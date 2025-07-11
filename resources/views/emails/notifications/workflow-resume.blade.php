@component('mail::message')
# 📊 Résumé Quotidien Workflow

Bonjour,
Voici un résumé des demandes en attente aujourd'hui.

@component('mail::button', ['url' => config('app.url') . '/admin'])
Voir le dashboard
@endcomponent
@endcomponent
