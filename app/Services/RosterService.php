<?php

namespace App\Services;

use App\Exceptions\RosterException;
use App\Models\Entry;
use App\Models\RosterSlot;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * F13 – Roster and Substitution Rules.
 *
 * Rules enforced here:
 *  - roster size: starters and substitutes are capped (config/roster.php); while a team is entered in a
 *    tournament the starter count must also equal that tournament's team_size
 *  - lock window: no roster edits once the tournament is running or roster_lock_at has passed
 *  - eligibility: an added or substituted-in player must be a player account, within the rating range of
 *    every tournament the team has entered, and not on another team entered in the same tournament
 *
 * Every change runs in a transaction and locks the team row, so two simultaneous requests cannot both
 * squeeze past a size limit.
 */
class RosterService
{
    private const PLAYER_ROLES = ['player', 'team_captain'];
    private const DEFAULT_RATING = 1000;

    public function createTeam(User $captain, string $name): Team
    {
        if (! in_array($captain->role, self::PLAYER_ROLES, true)) {
            throw new RosterException('Only players can create a team.');
        }

        return DB::transaction(function () use ($captain, $name) {
            $captain = User::whereKey($captain->id)->lockForUpdate()->firstOrFail();

            if (Team::where('captain_id', $captain->id)->exists()) {
                throw new RosterException('You already captain a team. A captain can lead only one team.');
            }

            $team = Team::create(['name' => trim($name), 'captain_id' => $captain->id]);
            $team->addMember($captain, false); // the captain is the first starter

            if ($captain->role === 'player') {
                $captain->forceFill(['role' => 'team_captain'])->save();
            }

            return $team;
        });
    }

    public function renameTeam(Team $team, string $name): Team
    {
        return $this->withTeamLock($team, function (Team $team) use ($name) {
            $this->assertNotLocked($team);
            $team->update(['name' => trim($name)]);

            return $team;
        });
    }

    public function deleteTeam(Team $team): void
    {
        $this->withTeamLock($team, function (Team $team) {
            $this->assertNotLocked($team);

            if ($team->activeEntries()->exists()) {
                throw new RosterException('This team is entered in a tournament. Withdraw the entry before deleting the team.');
            }

            $captain = User::find($team->captain_id);
            $team->delete(); // roster slots are removed with the team

            if ($captain && $captain->role === 'team_captain') {
                $captain->forceFill(['role' => 'player'])->save();
            }
        });
    }

    /** Add a player (found by email) as a starter or a substitute. */
    public function addPlayer(Team $team, string $email, bool $asSubstitute = false): RosterSlot
    {
        return $this->withTeamLock($team, function (Team $team) use ($email, $asSubstitute) {
            $this->assertNotLocked($team);

            $player = User::where('email', strtolower(trim($email)))->first();
            if (! $player) {
                throw new RosterException('No account was found with that email address.');
            }
            if (! in_array($player->role, self::PLAYER_ROLES, true)) {
                throw new RosterException("{$player->name} does not have a player account.");
            }
            if ($team->rosterSlots()->where('player_id', $player->id)->exists()) {
                throw new RosterException("{$player->name} is already on this roster.");
            }

            $limits = $this->limits($team);
            if ($asSubstitute) {
                if ($team->substitutes()->count() >= $limits['max_substitutes']) {
                    throw new RosterException("A roster can have at most {$limits['max_substitutes']} substitutes.");
                }
            } elseif ($team->starters()->count() >= $limits['max_starters']) {
                throw new RosterException($limits['tournament_bound']
                    ? "This team is entered in a tournament that needs exactly {$limits['max_starters']} starters. Add the player as a substitute instead."
                    : "A roster can have at most {$limits['max_starters']} starters.");
            }

            if ($problem = $this->eligibilityProblem($team, $player)) {
                throw new RosterException($problem);
            }

            return $team->addMember($player, $asSubstitute);
        });
    }

    public function removePlayer(Team $team, RosterSlot $slot): void
    {
        $this->withTeamLock($team, function (Team $team) use ($slot) {
            $this->assertNotLocked($team);

            $slot = $team->rosterSlots()->whereKey($slot->getKey())->first();
            if (! $slot) {
                throw new RosterException('That player is not on this roster.');
            }
            if ((int) $slot->player_id === (int) $team->captain_id) {
                throw new RosterException('The captain cannot be removed from the roster.');
            }

            if (! $slot->is_substitute) {
                $min = $this->limits($team)['min_starters'];
                if ($team->starters()->count() - 1 < $min) {
                    throw new RosterException("Removing this player would leave fewer than {$min} starters, which the team's tournament entry requires. Substitute a starter instead.");
                }
            }

            $slot->delete();
        });
    }

    /** Swap a starter with a substitute. The number of starters stays the same. */
    public function substitute(Team $team, int $starterSlotId, int $substituteSlotId): void
    {
        $this->withTeamLock($team, function (Team $team) use ($starterSlotId, $substituteSlotId) {
            $this->assertNotLocked($team);

            $starter = $team->starters()->whereKey($starterSlotId)->with('player')->first();
            $sub = $team->substitutes()->whereKey($substituteSlotId)->with('player')->first();
            if (! $starter || ! $sub) {
                throw new RosterException('Choose one starter and one substitute from this roster.');
            }

            // Ratings and other teams' entries can change after a player was added, so check again.
            if ($problem = $this->eligibilityProblem($team, $sub->player)) {
                throw new RosterException($problem);
            }

            $starter->update(['is_substitute' => true]);
            $sub->update(['is_substitute' => false]);
        });
    }

    /**
     * Roster size limits right now. A team with no tournament entry can build up to the configured size.
     * A team entered in tournaments must keep its starter count between the largest and smallest team_size.
     */
    public function limits(Team $team): array
    {
        $sizes = $team->activeEntries()->get()
            ->map(fn ($entry) => (int) $entry->tournament->team_size)
            ->filter();

        $configured = (int) config('roster.max_starters', 5);

        return [
            'min_starters' => $sizes->isEmpty() ? 1 : $sizes->max(),
            'max_starters' => $sizes->isEmpty() ? $configured : min($configured, $sizes->min()),
            'max_substitutes' => (int) config('roster.max_substitutes', 2),
            'tournament_bound' => $sizes->isNotEmpty(),
        ];
    }

    /** Make each slot's locked flag match the team's real lock state. */
    public function syncLocks(Team $team): void
    {
        $locked = $team->isLocked();
        $team->rosterSlots()->where('locked', ! $locked)->update(['locked' => $locked]);
    }

    // ------------------------------------------------------------------ internals

    private function withTeamLock(Team $team, callable $callback)
    {
        return DB::transaction(fn () => $callback(Team::whereKey($team->getKey())->lockForUpdate()->firstOrFail()));
    }

    private function assertNotLocked(Team $team): void
    {
        if ($team->isLocked()) {
            throw new RosterException('The roster is locked because the team is in a running tournament or the roster lock time has passed.');
        }
    }

    /** Eligibility against every tournament the team has entered. Returns a plain-language reason or null. */
    private function eligibilityProblem(Team $team, User $player): ?string
    {
        $rating = $player->rating ?? self::DEFAULT_RATING;
        $otherTeamIds = RosterSlot::where('player_id', $player->id)
            ->where('team_id', '!=', $team->id)
            ->pluck('team_id');

        foreach ($team->activeEntries()->get() as $entry) {
            $t = $entry->tournament;

            if ($t->min_rating !== null && $rating < $t->min_rating) {
                return "{$player->name}'s rating ({$rating}) is below the minimum of {$t->min_rating} for {$t->name}, which this team has entered.";
            }
            if ($t->max_rating !== null && $rating > $t->max_rating) {
                return "{$player->name}'s rating ({$rating}) is above the maximum of {$t->max_rating} for {$t->name}, which this team has entered.";
            }

            $clash = Entry::where('tournament_id', $t->id)
                ->whereIn('team_id', $otherTeamIds)
                ->whereIn('status', [Entry::CONFIRMED, Entry::WAITLISTED])
                ->exists();
            if ($clash) {
                return "{$player->name} is already on another team entered in {$t->name}.";
            }
        }

        return null;
    }
}
