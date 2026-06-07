<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sweepstake_id',
    'name',
    'email',
    'join_token',
    'source',
    'is_admin',
    'is_paid',
    'paid_at',
])]
class SweepstakeMember extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_JOIN_LINK = 'join_link';

    public const SOURCE_PIN = 'pin';

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_MANUAL => 'Added manually',
            self::SOURCE_PIN => 'Joined by PIN',
            default => 'Joined by link',
        };
    }
}
