<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sweepstake_id',
    'version_number',
    'status',
    'reason',
    'ran_at',
    'rerun_of_draw_id',
    'leftover_strategy',
    'selected_team_count',
    'base_teams_per_member',
    'leftover_team_count',
    'cancelled_reason',
    'cancelled_at',
])]
class SweepstakeDraw extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_CANCELLED = 'cancelled';

    public const LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED = 'remove_lowest_ranked';

    public const LEFTOVER_STRATEGY_ASSIGN_RANDOMLY = 'assign_randomly';

    protected function casts(): array
    {
        return [
            'base_teams_per_member' => 'integer',
            'cancelled_at' => 'datetime',
            'leftover_team_count' => 'integer',
            'ran_at' => 'datetime',
            'selected_team_count' => 'integer',
            'version_number' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active draw',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Superseded',
        };
    }

    public function leftoverStrategyLabel(): string
    {
        return match ($this->leftover_strategy) {
            self::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY => 'Randomly assigned leftover teams',
            self::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED => 'Removed leftover teams for an even draw',
            default => 'Not recorded',
        };
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function rerunOfDraw(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rerun_of_draw_id');
    }
}
