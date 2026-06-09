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
    'position',
    'teams_per_entrant',
])]
class SweepstakePot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'teams_per_entrant' => 'integer',
        ];
    }

    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function potTeams(): HasMany
    {
        return $this->hasMany(SweepstakePotTeam::class)->orderBy('position')->orderBy('id');
    }
}
