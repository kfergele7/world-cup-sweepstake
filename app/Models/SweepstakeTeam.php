<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'sweepstake_id',
    'team_id',
    'is_included',
    'is_removed',
    'removed_reason',
    'pot_number',
    'sort_order',
])]
class SweepstakeTeam extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'is_removed' => 'boolean',
            'pot_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function potAssignment(): HasOne
    {
        return $this->hasOne(SweepstakePotTeam::class);
    }
}
