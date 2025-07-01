<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelanceFournisseur extends Model
{
    use HasFactory;

    protected $table = 'relance_fournisseurs'; // Explicit table name

    protected $fillable = [
        'commande_id',
        'niveau',
        'email_envoye',
        'destinataire',
        'template_utilise',
    ];

    protected $casts = [
        'email_envoye' => 'datetime',
        'niveau' => 'integer',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
