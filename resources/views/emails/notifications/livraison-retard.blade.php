@component('mail::message')
# 📦 Livraison en Retard

La commande {{ $commande->numero_commande }} accuse un retard de {{ $commande->joursRetard() }} jour(s).

@component('mail::button', ['url' => config('app.url') . '/admin/commandes/' . $commande->id])
Suivi de la commande
@endcomponent
@endcomponent
