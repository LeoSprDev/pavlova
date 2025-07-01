<?php

namespace App\Models;

// This is the model from RingleSoft\LaravelProcessApproval package.
// We are creating it here to ensure it exists and can be referenced,
// especially if the package doesn't auto-create it or if we need to customize it.
// However, typically, you would extend or use the package's model directly.
// If the package provides RingleSoft\LaravelProcessApproval\Models\ProcessApproval,
// this file might conflict or be unnecessary.
// For now, creating a basic one as per standard model structure,
// assuming it's needed or for reference.

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval as BaseProcessApproval;


// class ProcessApproval extends Model // If we were defining it from scratch
class ProcessApproval extends BaseProcessApproval // More likely, we extend the package's model
{
    // use HasFactory; // If extending, factory might be in the package or defined separately

    // protected $fillable = [
    //     'approvable_type',
    //     'approvable_id',
    //     'user_id',
    //     'step',
    //     'status',
    //     'comment',
    //     // Add any other fields specific to your implementation if not in base
    // ];

    // protected $casts = [
    //     // Define casts if necessary
    // ];

    // public function approvable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    // If just using the package's model, this file is a placeholder or for type hinting.
    // The RingleSoft\LaravelProcessApproval\Models\ProcessApproval model should already exist
    // after `composer require ringlesoft/laravel-process-approval`.
    // The `Approval` alias in DemandeDevis.php points to this.
}
