<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_settings' => 'array',
            'profile_completed_at' => 'datetime',
            'use_gravatar' => 'boolean',
        ];
    }

    /**
     * Check if user has completed profile setup
     */
    public function hasCompletedSetup(): bool
    {
        return ! is_null($this->profile_completed_at);
    }

    /**
     * Mark profile setup as complete
     */
    public function markSetupComplete(): void
    {
        $this->update(['profile_completed_at' => now()]);
    }

    /**
     * Get user's display name with fallback
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['display_name'] ?? $this->name ?? 'User';
    }

    /**
     * Get avatar URL (Gravatar or custom)
     */
    public function getAvatarUrlAttribute(): string
    {
        return app(\App\Services\AvatarService::class)->getAvatarUrl($this);
    }

    /**
     * Get profile setting by key with default
     */
    public function getProfileSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->profile_settings, $key, $default);
    }

    /**
     * Set profile setting by key
     */
    public function setProfileSetting(string $key, mixed $value): void
    {
        $settings = $this->profile_settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['profile_settings' => $settings]);
    }
}
