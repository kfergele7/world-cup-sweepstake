<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'join_code',
    'entry_fee',
    'currency',
    'status',
    'draw_mode',
    'teams_per_member',
    'leftover_rule',
    'drawn_at',
])]
class Sweepstake extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_DRAWN = 'drawn';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_COMPLETED = 'completed';

    public const DRAW_MODE_RANKED_POTS = 'ranked_pots';

    public const LEFTOVER_REMOVE_LOWEST_RANKED = 'remove_lowest_ranked';

    protected function casts(): array
    {
        return [
            'drawn_at' => 'datetime',
            'entry_fee' => 'decimal:2',
            'teams_per_member' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SweepstakeMember::class);
    }

    public function paidMembers(): HasMany
    {
        return $this->members()->where('is_paid', true);
    }

    public function entrants(): HasMany
    {
        return $this->members();
    }

    public function sweepstakeTeams(): HasMany
    {
        return $this->hasMany(SweepstakeTeam::class);
    }

    public function selectedSweepstakeTeams(): HasMany
    {
        return $this->sweepstakeTeams()
            ->where('is_included', true)
            ->where('is_removed', false);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(SweepstakeDraw::class)->orderBy('version_number');
    }

    public function activeDraw(): HasOne
    {
        return $this->hasOne(SweepstakeDraw::class)
            ->where('status', SweepstakeDraw::STATUS_ACTIVE)
            ->latestOfMany('version_number');
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class)->orderBy('position');
    }

    public function isLockedForChanges(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAWN,
            self::STATUS_LOCKED,
            self::STATUS_COMPLETED,
        ], true);
    }

    public function collectedPot(): float
    {
        return (float) $this->entry_fee * $this->paidMembers()->count();
    }
}
