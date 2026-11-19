<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    public const CONFIRMED = 'confirmed';
    public const WAITLISTED = 'waitlisted';
    public const WITHDRAWN = 'withdrawn';
    public const DISQUALIFIED = 'disqualified';

    protected $fillable = [
        'tournament_id', 'user_id', 'team_id', 'status', 'status_reason',
        'rating_snapshot', 'seed', 'entered_at', 'promoted_at', 'withdrawn_at',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'promoted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::CONFIRMED);
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', self::WAITLISTED);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::CONFIRMED, self::WAITLISTED], true);
    }

    /** Name shown on entry lists: team name for team entries, player name otherwise. */
    public function getDisplayNameAttribute(): string
    {
        return $this->team?->name ?? $this->user?->name ?? 'Unknown entrant';
    }

    /** 1-based place in the waitlist queue, or null if this entry is not waitlisted. */
    public function waitlistPosition(): ?int
    {
        if ($this->status !== self::WAITLISTED) {
            return null;
        }

        $ahead = self::where('tournament_id', $this->tournament_id)
            ->where('status', self::WAITLISTED)
            ->where(function ($q) {
                $q->where('entered_at', '<', $this->entered_at)
                  ->orWhere(function ($q) {
                      $q->where('entered_at', $this->entered_at)->where('id', '<', $this->id);
                  });
            })
            ->count();

        return $ahead + 1;
    }
}
