<?php

namespace App\Services;

use InvalidArgumentException;

class SeedingService
{
    public function seedByRating(
        array $participants,
        bool $separateTeammates = false,
        array $teammateGroups = []
    ): array {
        $participants = $this->validateParticipants($participants);

        usort($participants, function ($a, $b) {
            $ratingA = $a['rating'] ?? $a['rating_snapshot'] ?? 0;
            $ratingB = $b['rating'] ?? $b['rating_snapshot'] ?? 0;

            $comparison = $ratingB <=> $ratingA;

            return $comparison !== 0
                ? $comparison
                : ((string) $a['id'] <=> (string) $b['id']);
        });

        if ($separateTeammates) {
            $participants = $this->separateTeammates(
                $participants,
                $teammateGroups
            );
        }

        return $this->assignSeeds($participants);
    }

    public function seedRandomly(
        array $participants,
        bool $separateTeammates = false,
        array $teammateGroups = []
    ): array {
        $participants = $this->validateParticipants($participants);
        shuffle($participants);

        if ($separateTeammates) {
            $participants = $this->separateTeammates(
                $participants,
                $teammateGroups
            );
        }

        return $this->assignSeeds($participants);
    }

    public function seedManually(
        array $participants,
        array $manualOrder,
        bool $separateTeammates = false,
        array $teammateGroups = []
    ): array {
        $participants = $this->validateParticipants($participants);
        $participantsById = [];

        foreach ($participants as $participant) {
            $participantsById[(string) $participant['id']] = $participant;
        }

        if (count($manualOrder) !== count($participants)) {
            throw new InvalidArgumentException(
                'Manual order must include every participant exactly once.'
            );
        }

        $orderedParticipants = [];
        $usedIds = [];

        foreach ($manualOrder as $id) {
            $key = (string) $id;

            if (!array_key_exists($key, $participantsById) || isset($usedIds[$key])) {
                throw new InvalidArgumentException(
                    'Manual order contains an unknown or duplicate participant ID.'
                );
            }

            $orderedParticipants[] = $participantsById[$key];
            $usedIds[$key] = true;
        }

        if ($separateTeammates) {
            $orderedParticipants = $this->separateTeammates(
                $orderedParticipants,
                $teammateGroups
            );
        }

        return $this->assignSeeds($orderedParticipants);
    }

    private function validateParticipants(array $participants): array
    {
        if ($participants === []) {
            throw new InvalidArgumentException('At least one participant is required.');
        }

        $seen = [];

        foreach ($participants as $participant) {
            if (!is_array($participant) || !array_key_exists('id', $participant)) {
                throw new InvalidArgumentException('Every participant must have an ID.');
            }

            $id = (string) $participant['id'];

            if ($id === '' || isset($seen[$id])) {
                throw new InvalidArgumentException('Participant IDs must be non-empty and unique.');
            }

            $seen[$id] = true;
        }

        return array_values($participants);
    }

    /**
     * $teammateGroups may be [entry_id => group_id], as returned by
     * TeammateGroupService::groupsFor(). team_id/group_id fields are fallbacks.
     */
    private function separateTeammates(
        array $participants,
        array $teammateGroups = []
    ): array {
        $groups = [];

        foreach ($participants as $participant) {
            $id = (string) $participant['id'];

            if (array_key_exists($id, $teammateGroups)) {
                $groupId = $teammateGroups[$id];
            } elseif (array_key_exists($participant['id'], $teammateGroups)) {
                $groupId = $teammateGroups[$participant['id']];
            } else {
                $groupId = $participant['group_id']
                    ?? $participant['team_id']
                    ?? null;
            }

            $groupKey = $groupId === null
                ? 'individual_' . $id
                : 'group_' . (string) $groupId;

            $groups[$groupKey][] = $participant;
        }

        $separated = [];

        while ($groups !== []) {
            foreach (array_keys($groups) as $groupKey) {
                $separated[] = array_shift($groups[$groupKey]);

                if ($groups[$groupKey] === []) {
                    unset($groups[$groupKey]);
                }
            }
        }

        return $separated;
    }

    private function assignSeeds(array $participants): array
    {
        foreach ($participants as $index => &$participant) {
            $participant['seed'] = $index + 1;
        }

        unset($participant);

        return $participants;
    }
}