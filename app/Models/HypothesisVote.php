<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['hypothesis_id', 'user_id', 'confidence'])]
class HypothesisVote extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Hypothesis, $this>
     */
    public function hypothesis(): BelongsTo
    {
        return $this->belongsTo(Hypothesis::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
