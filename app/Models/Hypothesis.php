<?php

namespace App\Models;

use App\Enums\HypothesisStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['investigation_id', 'user_id', 'statement', 'status'])]
class Hypothesis extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => HypothesisStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Investigation, $this>
     */
    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<HypothesisVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(HypothesisVote::class);
    }
}
