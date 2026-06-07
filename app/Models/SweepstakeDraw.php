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
])]
class SweepstakeDraw extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUPERSEDED = 'superseded';

    protected function casts(): array
    {
        return [
            'ran_at' => 'datetime',
            'version_number' => 'integer',
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

    public function rerunOfDraw(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rerun_of_draw_id');
    }
}
