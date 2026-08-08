<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['investigation_id', 'user_id', 'title', 'body', 'source_url'])]
class Evidence extends Model
{
    use HasFactory;

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
}
