<?php

namespace App\Services;

use InvalidArgumentException;

class SwissPairingService
{
    /**
     * Generate score-based Swiss pairings.
     *
     * For up to 16 players, a bounded dynamic-programming search finds a
     * pairing that first minimizes rematches, then minimizes score gaps.
     * Larger fields use a deterministic greedy fallback to keep runtime safe.
     *
     * Input participant:
     * id, score, opponents[] (optional), has_received_bye (optional)
     */
    public function generatePairings(array $participants): array
    {
        if ($participants === []) {
            throw new InvalidArgumentException('At least one participant is required.');
        }

        $participants = $this->validateParticipants($participants);

        usort($participants, function ($a, $b) {
            $comparison = $b['score'] <=> $a['score'];

            return $comparison !== 0
                ? $comparison
                : ((string) $a['id'] <=> (string) $b['id']);
        });

        $bye = null;

        if (count($participants) % 2 !== 0) {
            for ($i = count($participants) - 1; $i >= 0; $i--) {
                if (!$participants[$i]['has_received_bye']) {
                    $bye = $participants[$i];
                    $bye['has_received_bye'] = true;
                    array_splice($participants, $i, 1);
                    break;
                }
            }

            if ($bye === null) {
                throw new InvalidArgumentException(
                    'No eligible participant is available for a bye; every participant has already received one.'
                );
            }
        }

        $pairs = count($participants) <= 16
            ? $this->optimalPairs($participants)
            : $this->greedyPairs($participants);

        $matches = [];

        foreach ($pairs as [$firstIndex, $secondIndex]) {
            $first = $participants[$firstIndex];
            $second = $participants[$secondIndex];
            $rematch = $this->hasPlayed($first, $second);

            $matches[] = [
                'player1' => $first,
                'player2' => $second,
                'rematch' => $rematch,
                'score_difference' => abs($first['score'] - $second['score']),
            ];
        }

        return [
            'matches' => $matches,
            'bye' => $bye,
        ];
    }

    private function validateParticipants(array $participants): array
    {
        $seen = [];

        foreach ($participants as &$participant) {
            if (!is_array($participant)
                || !array_key_exists('id', $participant)
                || !array_key_exists('score', $participant)) {
                throw new InvalidArgumentException(
                    'Every participant must have an ID and score.'
                );
            }

            if (!is_numeric($participant['score'])) {
                throw new InvalidArgumentException('Participant scores must be numeric.');
            }

            $id = (string) $participant['id'];

            if ($id === '' || isset($seen[$id])) {
                throw new InvalidArgumentException('Participant IDs must be non-empty and unique.');
            }

            $seen[$id] = true;
            $participant['score'] = (float) $participant['score'];
            $participant['opponents'] = $participant['opponents'] ?? [];
            $participant['has_received_bye'] = (bool) ($participant['has_received_bye'] ?? false);

            if (!is_array($participant['opponents'])) {
                throw new InvalidArgumentException('Opponent history must be an array.');
            }

            $participant['opponents'] = array_map(
                static fn ($opponentId) => (string) $opponentId,
                $participant['opponents']
            );
        }

        unset($participant);

        return array_values($participants);
    }

    private function hasPlayed(array $first, array $second): bool
    {
        return in_array((string) $second['id'], $first['opponents'], true)
            || in_array((string) $first['id'], $second['opponents'], true);
    }

    /**
     * Exact minimum-cost pairing for fields up to 16.
     * Objective priority: fewest rematches, then smallest total score gap.
     */
    private function optimalPairs(array $participants): array
    {
        $count = count($participants);
        if ($count === 0) {
            return [];
        }

        $fullMask = (1 << $count) - 1;
        $memo = [];

        $solve = function (int $mask) use (&$solve, &$memo, $participants, $count): array {
            if ($mask === 0) {
                return ['rematches' => 0, 'gap' => 0.0, 'pairs' => []];
            }

            if (isset($memo[$mask])) {
                return $memo[$mask];
            }

            $first = null;
            for ($i = 0; $i < $count; $i++) {
                if (($mask & (1 << $i)) !== 0) {
                    $first = $i;
                    break;
                }
            }

            $remainingMask = $mask & ~(1 << $first);
            $best = null;

            for ($j = $first + 1; $j < $count; $j++) {
                if (($remainingMask & (1 << $j)) === 0) {
                    continue;
                }

                $nextMask = $remainingMask & ~(1 << $j);
                $tail = $solve($nextMask);
                $isRematch = $this->hasPlayed($participants[$first], $participants[$j]);
                $candidate = [
                    'rematches' => $tail['rematches'] + ($isRematch ? 1 : 0),
                    'gap' => $tail['gap'] + abs(
                        $participants[$first]['score'] - $participants[$j]['score']
                    ),
                    'pairs' => array_merge([[$first, $j]], $tail['pairs']),
                ];

                if ($best === null
                    || $candidate['rematches'] < $best['rematches']
                    || ($candidate['rematches'] === $best['rematches']
                        && $candidate['gap'] < $best['gap'])) {
                    $best = $candidate;
                }
            }

            $memo[$mask] = $best;

            return $best;
        };

        return $solve($fullMask)['pairs'];
    }

    /**
     * Runtime-safe fallback for larger tournaments.
     * Selects the remaining player with the fewest rematch-free options,
     * then pairs them with the closest score among available opponents.
     */
    private function greedyPairs(array $participants): array
    {
        $remaining = array_keys($participants);
        $pairs = [];

        while (count($remaining) > 0) {
            $selectedPosition = 0;
            $fewestOptions = PHP_INT_MAX;

            foreach ($remaining as $position => $firstIndex) {
                $options = 0;

                foreach ($remaining as $secondIndex) {
                    if ($firstIndex !== $secondIndex
                        && !$this->hasPlayed($participants[$firstIndex], $participants[$secondIndex])) {
                        $options++;
                    }
                }

                if ($options < $fewestOptions) {
                    $fewestOptions = $options;
                    $selectedPosition = $position;
                }
            }

            $firstIndex = $remaining[$selectedPosition];
            unset($remaining[$selectedPosition]);
            $remaining = array_values($remaining);

            $bestPosition = null;
            $bestRematch = true;
            $bestGap = PHP_FLOAT_MAX;

            foreach ($remaining as $position => $secondIndex) {
                $rematch = $this->hasPlayed(
                    $participants[$firstIndex],
                    $participants[$secondIndex]
                );
                $gap = abs(
                    $participants[$firstIndex]['score'] - $participants[$secondIndex]['score']
                );

                if (($bestRematch && !$rematch)
                    || ($bestRematch === $rematch && $gap < $bestGap)) {
                    $bestPosition = $position;
                    $bestRematch = $rematch;
                    $bestGap = $gap;
                }
            }

            $secondIndex = $remaining[$bestPosition];
            unset($remaining[$bestPosition]);
            $remaining = array_values($remaining);

            $pairs[] = [$firstIndex, $secondIndex];
        }

        return $pairs;
    }
}