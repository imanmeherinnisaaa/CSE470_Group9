<?php

namespace App\Models\Concerns;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * F2 helpers for the Tournament model (the model itself belongs to F1).
 * Add `use HasEntryRules;` inside the Tournament class and nothing else needs to change.
 */
trait HasEntryRules
{
    /** Laravel calls initialize<TraitName>() when a model boots, so the casts come with the trait. */
    public function initializeHasEntryRules(): void
    {
        $this->mergeCasts([
            'entry_opens_at' => 'datetime',
            'entry_closes_at' => 'datetime',
        ]);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /** The entrants who hold a place in the tournament. Seeding, brackets and pairings should read this. */
    public function confirmedEntries(): HasMany
    {
        return $this->entries()->where('status', Entry::CONFIRMED);
    }

    public function isTeamBased(): bool
    {
        return $this->entry_type === 'team';
    }

    /** Registration phase: before the organizer generates the bracket. */
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration';
    }

    /**
     * [opens, closes] for entering. Uses the registration dates set on F1's tournament form,
     * and falls back to entry_opens_at / entry_closes_at when those are empty.
     */
    public function entryWindow(): array
    {
        return [
            $this->registration_opens_at ?? $this->entry_opens_at,
            $this->registration_closes_at ?? $this->entry_closes_at,
        ];
    }

    public function isEntryWindowOpen(): bool
    {
        if (! $this->isRegistrationOpen()) {
            return false;
        }

        [$opens, $closes] = $this->entryWindow();
        $now = now();

        if ($opens && $now->lt($opens)) {
            return false;
        }
        if ($closes && $now->gt($closes)) {
            return false;
        }

        return true;
    }

    public function confirmedCount(): int
    {
        return $this->entries()->where('status', Entry::CONFIRMED)->count();
    }

    /** A null max_participants means no cap. */
    public function hasOpenSlot(): bool
    {
        return $this->max_participants === null
            || $this->confirmedCount() < (int) $this->max_participants;
    }
}
