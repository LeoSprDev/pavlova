@component('mail::message')
# 🔄 Demande Escaladée

La demande #{{ $demande->id }} a dépassé le délai de traitement.

@component('mail::button', ['url' => config('app.url') . '/admin/demande-devis/' . $demande->id])
Voir la demande
@endcomponent
@endcomponent
