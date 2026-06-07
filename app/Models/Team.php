<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'country_code',
    'flag',
    'fifa_ranking',
    'ranking_points',
    'qualified_for_2026',
    'confederation',
])]
class Team extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fifa_ranking' => 'integer',
            'qualified_for_2026' => 'boolean',
            'ranking_points' => 'decimal:2',
        ];
    }

    public function sweepstakeTeams(): HasMany
    {
        return $this->hasMany(SweepstakeTeam::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }
}
