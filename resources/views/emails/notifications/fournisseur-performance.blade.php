@component('mail::message')
# 📈 Rapport Performance Fournisseur

Le fournisseur {{ $fournisseur->nom }} présente un taux de retard élevé.

@component('mail::button', ['url' => config('app.url') . '/admin/fournisseurs/' . $fournisseur->id])
Consulter le rapport
@endcomponent
@endcomponent
