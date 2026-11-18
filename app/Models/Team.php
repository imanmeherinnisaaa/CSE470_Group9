<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** Tournament statuses after which a team is free again. */
    public const FINISHED_STATUSES = ['completed', 'archived', 'cancelled'];

    protected $fillable = ['name', 'captain_id'];

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function rosterSlots(): HasMany
    {
        return $this->hasMany(RosterSlot::class);
    }

    public function starters(): HasMany
    {
        return $this->rosterSlots()->where('is_substitute', false);
    }

    public function substitutes(): HasMany
    {
        return $this->rosterSlots()->where('is_substitute', true);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /** Entries (confirmed or waitlisted) in tournaments that have not finished. */
    public function activeEntries(): HasMany
    {
        return $this->entries()
            ->whereIn('status', [Entry::CONFIRMED, Entry::WAITLISTED])
            ->whereHas('tournament', fn ($q) => $q->whereNotIn('status', self::FINISHED_STATUSES))
            ->with('tournament');
    }

    /** Persists a roster slot. All rules live in RosterService, which validates before calling this. */
    public function addMember(User $player, bool $isSubstitute = false): RosterSlot
    {
        return $this->rosterSlots()->create([
            'player_id' => $player->id,
            'is_substitute' => $isSubstitute,
        ]);
    }

    /**
     * The roster is locked while the team is entered in a tournament that is past its lock point:
     * either the tournament is running, or it is still in registration and roster_lock_at has passed.
     */
    public function isLocked(): bool
    {
        foreach ($this->activeEntries()->get() as $entry) {
            $tournament = $entry->tournament;

            if ($tournament->status === 'registration') {
                if ($tournament->roster_lock_at && Carbon::parse($tournament->roster_lock_at)->lte(now())) {
                    return true;
                }
            } elseif ($tournament->status !== 'draft') {
                return true; // bracket generated or event running
            }
        }

        return false;
    }
}
