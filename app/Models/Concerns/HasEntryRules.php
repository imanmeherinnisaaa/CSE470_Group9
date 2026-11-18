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

    public function isTeamBased(): bool
    {
        return $this->entry_type === 'team';
    }

    /** Registration phase: before the organizer generates the bracket. */
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration';
    }

    public function isEntryWindowOpen(): bool
    {
        if (! $this->isRegistrationOpen()) {
            return false;
        }
        $now = now();
        if ($this->entry_opens_at && $now->lt($this->entry_opens_at)) {
            return false;
        }
        if ($this->entry_closes_at && $now->gt($this->entry_closes_at)) {
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
