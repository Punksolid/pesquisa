<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'email_verified_at', 'passport_client_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'points' => 'integer',
        ];
    }

    /**
     * @return HasMany<Investigation, $this>
     */
    public function investigations(): HasMany
    {
        return $this->hasMany(Investigation::class, 'created_by');
    }

    /**
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * @return HasMany<Hypothesis, $this>
     */
    public function hypotheses(): HasMany
    {
        return $this->hasMany(Hypothesis::class);
    }

    /**
     * @return HasMany<HypothesisVote, $this>
     */
    public function hypothesisVotes(): HasMany
    {
        return $this->hasMany(HypothesisVote::class);
    }

    public function awardPoints(int $points): void
    {
        $this->increment('points', $points);
    }
}
