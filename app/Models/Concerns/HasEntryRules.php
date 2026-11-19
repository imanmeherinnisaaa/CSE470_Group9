<?php

namespace App\Models\Concerns;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasEntryRules
{
    public function initializeHasEntryRules(): void
    {
        $this->mergeCasts([
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
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

        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $now->gt($this->registration_closes_at)) {
            return false;
        }

        return true;
    }

    public function confirmedCount(): int
    {
        return $this->entries()
            ->where('status', Entry::CONFIRMED)
            ->count();
    }

    public function hasOpenSlot(): bool
    {
        return $this->max_participants === null
            || $this->confirmedCount() < (int) $this->max_participants;
    }
}
