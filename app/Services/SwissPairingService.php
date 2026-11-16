<?php

namespace App\Services;

use InvalidArgumentException;

class SwissPairingService
{
    public function generatePairings(array $participants): array
    {
        if (empty($participants)) {
            throw new InvalidArgumentException(
                'At least one participant is required.'
            );
        }

        $seenIds = [];

        // Validate participants and prepare their pairing information
        foreach ($participants as &$participant) {

            if (!isset($participant['id']) ||
                !isset($participant['score'])) {
                throw new InvalidArgumentException(
                    'Every participant must have an ID and score.'
                );
            }

            if (!is_numeric($participant['score'])) {
                throw new InvalidArgumentException(
                    'Participant scores must be numeric.'
                );
            }

            $id = (string) $participant['id'];

            if (isset($seenIds[$id])) {
                throw new InvalidArgumentException(
                    'Duplicate participant ID found.'
                );
            }

            $seenIds[$id] = true;

            if (!isset($participant['opponents'])) {
                $participant['opponents'] = [];
            }

            if (!isset($participant['has_received_bye'])) {
                $participant['has_received_bye'] = false;
            }

            if (!is_array($participant['opponents'])) {
                throw new InvalidArgumentException(
                    'Opponent history must be an array.'
                );
            }
        }

        unset($participant);

        // Sort participants by highest score first
        usort($participants, function ($a, $b) {
            $scoreComparison = $b['score'] <=> $a['score'];

            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return $a['id'] <=> $b['id'];
        });

        $bye = null;

        // An odd number of participants requires one bye
        if (count($participants) % 2 !== 0) {

            // Find the lowest-scoring participant who has not received a bye
            for ($i = count($participants) - 1; $i >= 0; $i--) {

                if (!$participants[$i]['has_received_bye']) {

                    $bye = $participants[$i];

                    // Mark the bye so it can be saved in the database
                    $bye['has_received_bye'] = true;

                    array_splice($participants, $i, 1);

                    break;
                }
            }

            if ($bye === null) {
                throw new InvalidArgumentException(
                    'No eligible participant is available for a bye.'
                );
            }
        }

        $matches = [];

        // Generate pairings
        while (count($participants) > 0) {

            $firstPlayer = array_shift($participants);

            $bestIndex = null;
            $bestDifference = PHP_INT_MAX;

            // Find the closest-scoring player who has not played this player
            foreach ($participants as $index => $secondPlayer) {

                $hasPlayedBefore =
                    in_array(
                        $secondPlayer['id'],
                        $firstPlayer['opponents'],
                        true
                    )
                    ||
                    in_array(
                        $firstPlayer['id'],
                        $secondPlayer['opponents'],
                        true
                    );

                $difference = abs(
                    $firstPlayer['score'] - $secondPlayer['score']
                );

                if (!$hasPlayedBefore &&
                    $difference < $bestDifference) {

                    $bestDifference = $difference;
                    $bestIndex = $index;
                }
            }

            $rematch = false;

            // If no rematch-free opponent exists, choose the closest score
            if ($bestIndex === null) {

                $rematch = true;

                foreach ($participants as $index => $secondPlayer) {

                    $difference = abs(
                        $firstPlayer['score'] - $secondPlayer['score']
                    );

                    if ($difference < $bestDifference) {
                        $bestDifference = $difference;
                        $bestIndex = $index;
                    }
                }
            }

            // Remove the selected opponent from the remaining participants
            $secondPlayer = array_splice(
                $participants,
                $bestIndex,
                1
            )[0];

            $matches[] = [
                'player1' => $firstPlayer,
                'player2' => $secondPlayer,
                'rematch' => $rematch,
                'score_difference' => $bestDifference
            ];
        }

        return [
            'matches' => $matches,
            'bye' => $bye
        ];
    }
}
