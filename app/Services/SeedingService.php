<?php

namespace App\Services;

class SeedingService
{
    // Method 1: Seed participants according to rating
    public function seedByRating(
        array $participants,
        bool $separateTeammates = false
    ): array {
        usort($participants, function ($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });

        if ($separateTeammates) {
            $participants = $this->separateTeammates($participants);
        }

        return $this->assignSeeds($participants);
    }

    // Method 2: Seed participants randomly
    public function seedRandomly(
        array $participants,
        bool $separateTeammates = false
    ): array {
        shuffle($participants);

        if ($separateTeammates) {
            $participants = $this->separateTeammates($participants);
        }

        return $this->assignSeeds($participants);
    }

    // Method 3: Seed participants in the organizer's chosen order
    public function seedManually(
        array $participants,
        array $manualOrder,
        bool $separateTeammates = false
    ): array {
        $participantsById = [];

        foreach ($participants as $participant) {
            $participantsById[$participant['id']] = $participant;
        }

        if (count($manualOrder) !== count($participants)) {
            throw new \InvalidArgumentException(
                'Manual order must include every participant exactly once.'
            );
        }

        $orderedParticipants = [];
        $usedIds = [];

        foreach ($manualOrder as $id) {
            if (!isset($participantsById[$id]) || isset($usedIds[$id])) {
                throw new \InvalidArgumentException(
                    'Invalid or duplicate participant ID.'
                );
            }

            $orderedParticipants[] = $participantsById[$id];
            $usedIds[$id] = true;
        }

        if ($separateTeammates) {
            $orderedParticipants = $this->separateTeammates(
                $orderedParticipants
            );
        }

        return $this->assignSeeds($orderedParticipants);
    }

    // Method 4: Spread teammates across the seed order
    private function separateTeammates(array $participants): array
    {
        $teams = [];

        foreach ($participants as $participant) {
            if (
                isset($participant['team_id']) &&
                $participant['team_id'] !== null
            ) {
                $group = 'team_' . $participant['team_id'];
            } else {
                $group = 'player_' . $participant['id'];
            }

            $teams[$group][] = $participant;
        }

        $separated = [];

        while (!empty($teams)) {
            foreach ($teams as $group => $members) {
                $separated[] = array_shift($teams[$group]);

                if (empty($teams[$group])) {
                    unset($teams[$group]);
                }
            }
        }

        return $separated;
    }

    // Assign seed numbers to participants
    private function assignSeeds(array $participants): array
    {
        foreach ($participants as $index => &$participant) {
            $participant['seed'] = $index + 1;
        }

        unset($participant);

        return $participants;
    }
}
