<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sweepstake_pot_id',
    'sweepstake_team_id',
    'position',
])]
class SweepstakePotTeam extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function pot(): BelongsTo
    {
        return $this->belongsTo(SweepstakePot::class, 'sweepstake_pot_id');
    }

    public function sweepstakeTeam(): BelongsTo
    {
        return $this->belongsTo(SweepstakeTeam::class);
    }
}
