<?php

namespace App\Services;

use App\Exceptions\EntryException;
use App\Models\Entry;
use App\Models\RosterSlot;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * F2 – Entry, Eligibility and Waitlist.
 *
 * Every public method runs in a transaction and locks the tournament row first, so two people
 * pressing "Enter" for the last slot at the same moment cannot both get it.
 */
class EntryService
{
    public const DEFAULT_RATING = 1000;

    private const PLAYER_ROLES = ['player', 'team_captain'];

    /** Enter a tournament. Returns a confirmed entry, or a waitlisted one when the cap is reached. */
    public function enter(Tournament $tournament, User $user, ?Team $team = null): Entry
    {
        return DB::transaction(function () use ($tournament, $user, $team) {
            $tournament = $this->lockTournament($tournament);

            $this->assertMayEnter($tournament, $user, $team);

            $existing = $this->findExisting($tournament, $user, $team);
            if ($existing?->status === Entry::DISQUALIFIED) {
                throw new EntryException('This entrant was disqualified and cannot enter again.');
            }
            if ($existing?->isActive()) {
                throw new EntryException('You are already entered in this tournament.');
            }

            // Fill free slots from the waitlist first so a new entrant never jumps the queue.
            $this->fillOpenSlots($tournament);

            $attributes = [
                'status' => $tournament->hasOpenSlot() ? Entry::CONFIRMED : Entry::WAITLISTED,
                'status_reason' => null,
                'rating_snapshot' => $this->ratingOf($user, $team),
                'entered_at' => now(),
                'promoted_at' => null,
                'withdrawn_at' => null,
            ];

            if ($existing) { // re-entry after a withdrawal re-uses the same row
                $existing->update($attributes);
                return $existing->refresh();
            }

            return $tournament->entries()->create($attributes + [
                'user_id' => $team ? null : $user->id,
                'team_id' => $team?->id,
            ]);
        });
    }

    /** Withdraw an entry. A freed confirmed slot is given to the first eligible waitlisted entrant. */
    public function withdraw(Entry $entry): Entry
    {
        return DB::transaction(function () use ($entry) {
            $tournament = $this->lockTournament($entry->tournament);
            $entry = Entry::whereKey($entry->getKey())->lockForUpdate()->firstOrFail();

            if (! $entry->isActive()) {
                throw new EntryException('This entry has already been withdrawn or removed.');
            }
            if (! $tournament->isRegistrationOpen()) {
                throw new EntryException('The bracket has already been created. Ask the organizer to remove you from the event.');
            }

            $wasConfirmed = $entry->status === Entry::CONFIRMED;

            $entry->update([
                'status' => Entry::WITHDRAWN,
                'status_reason' => 'Withdrew voluntarily',
                'withdrawn_at' => now(),
            ]);
            Log::info('Entry withdrawn', ['tournament_id' => $tournament->id, 'entry_id' => $entry->id]);

            if ($wasConfirmed) {
                $this->fillOpenSlots($tournament);
            }

            return $entry;
        });
    }

    /**
     * Promote waitlisted entrants into any free slots. Called after withdrawals and by the
     * scheduler (entries:promote-waitlist). Safe to run repeatedly: when nothing is free it does nothing.
     */
    public function promoteFromWaitlist(Tournament $tournament): int
    {
        return DB::transaction(fn () => $this->fillOpenSlots($this->lockTournament($tournament)));
    }

    /** The entry (any status) that belongs to this user, either their own or their team's as captain. */
    public function entryFor(Tournament $tournament, User $user): ?Entry
    {
        return Entry::where('tournament_id', $tournament->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('team', fn ($t) => $t->where('captain_id', $user->id));
            })
            ->first();
    }

    // ------------------------------------------------------------------ internals

    private function lockTournament(Tournament $tournament): Tournament
    {
        return Tournament::whereKey($tournament->getKey())->lockForUpdate()->firstOrFail();
    }

    /** Caller must already hold the tournament lock. Returns how many entrants were promoted. */
    private function fillOpenSlots(Tournament $tournament): int
    {
        // Once the bracket exists, adding people would corrupt it.
        if (! $tournament->isRegistrationOpen()) {
            return 0;
        }

        $free = $tournament->max_participants === null
            ? PHP_INT_MAX
            : (int) $tournament->max_participants - $tournament->confirmedCount();

        if ($free <= 0) {
            return 0;
        }

        $promoted = 0;
        $queue = $tournament->entries()->where('status', Entry::WAITLISTED)
            ->with(['user', 'team'])
            ->orderBy('entered_at')->orderBy('id')
            ->get();

        foreach ($queue as $entry) {
            if ($free <= 0) {
                break;
            }

            // Ratings and rosters can change while someone waits, so check again before promoting.
            $problem = (! $entry->user && ! $entry->team)
                ? 'The account no longer exists.'
                : $this->eligibilityProblem($tournament, $entry->user, $entry->team);

            if ($problem) {
                $entry->update([
                    'status' => Entry::WITHDRAWN,
                    'status_reason' => 'No longer eligible: ' . $problem,
                    'withdrawn_at' => now(),
                ]);
                Log::info('Waitlisted entry skipped, no longer eligible', ['entry_id' => $entry->id, 'reason' => $problem]);
                continue;
            }

            $entry->update(['status' => Entry::CONFIRMED, 'promoted_at' => now()]);
            Log::info('Waitlist promotion', ['tournament_id' => $tournament->id, 'entry_id' => $entry->id]);
            $free--;
            $promoted++;
        }

        return $promoted;
    }

    private function assertMayEnter(Tournament $tournament, User $user, ?Team $team): void
    {
        if (! in_array($user->role, self::PLAYER_ROLES, true)) {
            throw new EntryException('Only players and team captains can enter a tournament.');
        }
        if (! $tournament->isEntryWindowOpen()) {
            throw new EntryException('Entry is closed. The entry window has not opened yet, has ended, or registration is over.');
        }
        if ($tournament->isTeamBased() && ! $team) {
            throw new EntryException('This is a team tournament. Choose a team to enter.');
        }
        if (! $tournament->isTeamBased() && $team) {
            throw new EntryException('This is a solo tournament. Teams cannot enter.');
        }
        if ($team && (int) $team->captain_id !== (int) $user->id) {
            throw new EntryException('Only the team captain can enter the team.');
        }

        if ($problem = $this->eligibilityProblem($tournament, $user, $team)) {
            throw new EntryException($problem);
        }
    }

    private function findExisting(Tournament $tournament, User $user, ?Team $team): ?Entry
    {
        $query = Entry::where('tournament_id', $tournament->id);

        return $team
            ? $query->where('team_id', $team->id)->first()
            : $query->where('user_id', $user->id)->first();
    }

    /** Rating and roster rules. Returns a plain-language reason, or null when eligible. */
    private function eligibilityProblem(Tournament $tournament, ?User $user, ?Team $team): ?string
    {
        if (! $team) {
            return $this->ratingProblem($tournament, $user, 'Your rating');
        }

        $starters = $this->starters($team);

        if ($starters->isEmpty()) {
            return 'The team has no starting players.';
        }
        if ($tournament->team_size && $starters->count() !== (int) $tournament->team_size) {
            return "This tournament needs exactly {$tournament->team_size} starting players per team. Your roster has {$starters->count()}.";
        }

        // A player can only play for one team per tournament.
        $otherTeamIds = RosterSlot::whereIn('player_id', $starters->pluck('id'))
            ->where('is_substitute', false)
            ->where('team_id', '!=', $team->id)
            ->pluck('team_id');

        $clash = Entry::where('tournament_id', $tournament->id)
            ->whereIn('team_id', $otherTeamIds)
            ->whereIn('status', [Entry::CONFIRMED, Entry::WAITLISTED])
            ->exists();

        if ($clash) {
            return 'One of your starting players is already entered with another team in this tournament.';
        }

        foreach ($starters as $player) {
            if ($problem = $this->ratingProblem($tournament, $player, "{$player->name}'s rating")) {
                return $problem;
            }
        }

        return null;
    }

    private function ratingProblem(Tournament $tournament, ?User $player, string $label): ?string
    {
        $rating = $player->rating ?? self::DEFAULT_RATING;

        if ($tournament->min_rating !== null && $rating < $tournament->min_rating) {
            return "{$label} ({$rating}) is below the minimum of {$tournament->min_rating} for this tournament.";
        }
        if ($tournament->max_rating !== null && $rating > $tournament->max_rating) {
            return "{$label} ({$rating}) is above the maximum of {$tournament->max_rating} for this tournament.";
        }

        return null;
    }

    /** Starting (non-substitute) players on the team's roster. Roster layout comes from F13. */
    private function starters(Team $team): Collection
    {
        $ids = RosterSlot::where('team_id', $team->id)->where('is_substitute', false)->pluck('player_id');

        return User::whereIn('id', $ids)->get();
    }

    /** Rating stored with the entry: the player's rating, or the rounded average of a team's starters. */
    private function ratingOf(User $user, ?Team $team): int
    {
        if (! $team) {
            return (int) ($user->rating ?? self::DEFAULT_RATING);
        }

        $starters = $this->starters($team);

        return $starters->isEmpty()
            ? self::DEFAULT_RATING
            : (int) round($starters->avg(fn ($p) => $p->rating ?? self::DEFAULT_RATING));
    }
}
