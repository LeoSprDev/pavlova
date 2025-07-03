<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Livraison extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'commande_id',
        'date_livraison',
        'statut_reception', // recue, en_attente, probleme_signalé, refusee
        'commentaire_reception',
        'verifie_par', // user_id
        'conforme',
        'actions_requises',
        'litige_en_cours',
        'note_qualite',
    ];

    protected $casts = [
        'date_livraison' => 'date',
        'conforme' => 'boolean',
        'litige_en_cours' => 'boolean',
        'note_qualite' => 'integer',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifie_par');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bons_livraison')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            // ->singleFile() // The prompt implies multiple BLs might be possible for one "Livraison" record if it represents an event
                           // but the Filament form example shows single file. Assuming single for now.
            ->singleFile()
            ->useFallbackUrl('/images/no-delivery.pdf') // Ensure this path is valid in public/images
            ->useFallbackPath(public_path('/images/no-delivery.pdf'));

        $this->addMediaCollection('photos_reception')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->useFallbackUrl('/images/no-photo.png')
            ->useFallbackPath(public_path('/images/no-photo.png'));
    }

    public function registerMediaConversions(Media $media = null): void
    {
        if ($media && str_starts_with($media->mime_type, 'image/')) {
            $this->addMediaConversion('thumbnail')
                ->width(150)
                ->height(150)
                ->sharpen(10)
                ->nonQueued(); // NonQueued for faster feedback on upload if displayed immediately

            $this->addMediaConversion('preview')
                ->width(500)
                ->height(500)
                ->nonQueued();
        }
    }

    protected static function booted(): void
    {
        static::saved(function (Livraison $livraison) {
            // Logic to update related models if necessary
            // For example, if this livraison completes a commande and it's conforme:
            if ($livraison->conforme && $livraison->statut_reception === 'recue') {
                $commande = $livraison->commande;
                if ($commande) {
                    $commande->statut = 'livree'; // Mark commande as delivered
                    $commande->saveQuietly(); // Avoid triggering other events if not needed

                    // Update budget ligne's montant_depense_reel
                    $demandeDevis = $commande->demandeDevis;
                    if ($demandeDevis && $demandeDevis->statut === 'delivered') { // Assuming DemandeDevis status is also updated
                        $budgetLigne = $demandeDevis->budgetLigne;
                        if ($budgetLigne) {
                            // This assumes that montant_depense_reel is the sum of all delivered items.
                            // It might be better to recalculate based on all delivered demands for that line.
                            // For simplicity here, if this is the only/last delivery for the demand, update.
                            // A more robust way is to sum all related delivered DemandeDevis for the BudgetLigne.
                            // $budgetLigne->montant_depense_reel = $budgetLigne->demandesApprouvees()->sum('prix_total_ttc');
                            // $budgetLigne->saveQuietly();
                            // Or, if montant_depense_reel in BudgetLigne is directly updated, this might not be needed here.
                            // The prompt for BudgetLigne model has calculateBudgetRestant based on sum of demandesApprouvees
                        }
                    }
                }
            }
        });
    }
}
