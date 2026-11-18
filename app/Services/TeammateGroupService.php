<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\RosterSlot;
use App\Models\Tournament;

/**
 * Tells the Seeding Engine (F3) which confirmed entrants are teammates.
 *
 * Returns [entry_id => group_id]. Two entries with the same group_id are teammates and should not
 * meet in the early rounds. group_id is just a label (the lowest entry id in the group).
 *
 *  - Team tournament: every entry is a whole team, so every entry is its own group.
 *  - Solo tournament: players are grouped when they share a team roster (starter or substitute).
 *    Grouping is transitive: if A and B share a team and B and C share another, A, B and C are one group.
 */
class TeammateGroupService
{
    public function groupsFor(Tournament $tournament): array
    {
        $entries = $tournament->entries()
            ->where('status', Entry::CONFIRMED)
            ->orderBy('id')
            ->get(['id', 'user_id', 'team_id']);

        $parent = [];
        foreach ($entries as $entry) {
            $parent[$entry->id] = $entry->id;
        }

        if (! $tournament->isTeamBased()) {
            $entryByPlayer = $entries->whereNotNull('user_id')->pluck('id', 'user_id'); // user_id => entry_id

            $slots = RosterSlot::whereIn('player_id', $entryByPlayer->keys())->get(['team_id', 'player_id']);

            foreach ($slots->groupBy('team_id') as $teamSlots) {
                $entryIds = $teamSlots
                    ->map(fn ($slot) => $entryByPlayer[$slot->player_id] ?? null)
                    ->filter()
                    ->values();

                foreach ($entryIds as $id) {
                    $this->union($parent, $entryIds->first(), $id);
                }
            }
        }

        $groups = [];
        foreach (array_keys($parent) as $id) {
            $groups[$id] = $this->find($parent, $id);
        }

        return $groups;
    }

    private function find(array &$parent, int $id): int
    {
        while ($parent[$id] !== $id) {
            $parent[$id] = $parent[$parent[$id]]; // path compression
            $id = $parent[$id];
        }

        return $id;
    }

    private function union(array &$parent, int $a, int $b): void
    {
        $rootA = $this->find($parent, $a);
        $rootB = $this->find($parent, $b);

        if ($rootA !== $rootB) {
            $parent[max($rootA, $rootB)] = min($rootA, $rootB); // lowest entry id becomes the label
        }
    }
}
