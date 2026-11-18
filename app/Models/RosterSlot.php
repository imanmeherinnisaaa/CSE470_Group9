<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterSlot extends Model
{
    protected $fillable = ['team_id', 'player_id', 'is_substitute', 'locked'];

    protected $casts = [
        'is_substitute' => 'boolean',
        'locked' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
