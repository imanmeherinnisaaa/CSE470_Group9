<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $table = 'game_matches';
    protected $guarded = [];

    protected $casts = [
        'is_bye' => 'boolean',
        'slot1_empty' => 'boolean',
        'slot2_empty' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function entry1(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry1_id');
    }

    public function entry2(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'winner_entry_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MatchReport::class, 'game_match_id');
    }

    public function slotLabel(int $slot): string
    {
        $entry = $slot === 1 ? $this->entry1 : $this->entry2;

        if ($entry) {
            return ($entry->seed ? "#{$entry->seed} " : '') . $entry->display_name;
        }

        return $this->{"slot{$slot}_empty"} ? 'BYE' : 'TBD';
    }
}
