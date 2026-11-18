<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReport extends Model
{
    protected $fillable = [
        'game_match_id',
        'reporter_id',
        'score1',
        'score2',
    ];

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
