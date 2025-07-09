<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements FilamentUser // MustVerifyEmail (optional, add if email verification is strictly needed)
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'service_id',
        'is_service_responsable',
        'force_password_change',
        'last_password_change',
        'email_verified_at', // Added for potential MustVerifyEmail
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_service_responsable' => 'boolean',
        'force_password_change' => 'boolean',
        'last_password_change' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function needsPasswordChange(): bool
    {
        return $this->force_password_change === true;
    }

    public function canValidateForService($serviceId): bool
    {
        return $this->hasRole('responsable-service')
            && $this->service_id === $serviceId
            && $this->is_service_responsable === true;
    }

    public function isAgentOfService($serviceId): bool
    {
        return $this->hasRole('agent-service')
            && $this->service_id === $serviceId;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     * Required by FilamentUser interface.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow all users who can log in to access the panel.
        // Specific resource/page access will be controlled by policies and Filament configurations.
        // Example: return str_ends_with($this->email, '@budget-workflow.local') && $this->hasVerifiedEmail();
        return true;
    }

    // If you have multiple panels, you might need to specify which panels a user can access.
    // For a single panel setup, the above is usually sufficient.
}
