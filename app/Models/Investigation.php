<?php

namespace App\Models;

use App\Enums\InvestigationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'summary', 'status', 'created_by'])]
class Investigation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => InvestigationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Investigation $investigation) {
            if (blank($investigation->slug)) {
                $investigation->slug = Str::slug($investigation->title).'-'.Str::random(6);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * @return BelongsTo<Hypothesis, $this>
     */
    public function confirmedHypothesis(): BelongsTo
    {
        return $this->belongsTo(Hypothesis::class, 'confirmed_hypothesis_id');
    }

    /**
     * @param  Builder<Investigation>  $query
     * @return Builder<Investigation>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', InvestigationStatus::Open);
    }
}
