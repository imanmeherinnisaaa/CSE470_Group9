<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;

class EntryPolicy
{
    /** A player can withdraw their own entry; a team captain can withdraw their team's entry. */
    public function withdraw(User $user, Entry $entry): bool
    {
        return (int) $entry->user_id === (int) $user->id
            || ($entry->team && (int) $entry->team->captain_id === (int) $user->id);
    }
}
