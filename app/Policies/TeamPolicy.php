<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /** Only the team's captain can change its roster, rename it or delete it. */
    public function manage(User $user, Team $team): bool
    {
        return (int) $team->captain_id === (int) $user->id;
    }
}
