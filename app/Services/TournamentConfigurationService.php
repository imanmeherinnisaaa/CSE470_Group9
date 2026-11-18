<?php

namespace App\Services;

use Carbon\Carbon;

class TournamentConfigurationService
{
    public const FORMATS = [
        'single_elimination' => 'Single Elimination',
        'double_elimination' => 'Double Elimination',
        'round_robin'        => 'Round Robin',
        'swiss'              => 'Swiss',
    ];

    private const MIN_PLAYERS = [
        'single_elimination' => 2,
        'double_elimination' => 4,
        'round_robin'        => 3,
        'swiss'              => 4,
    ];

    /** @return array<string, string[]> field => error messages (empty array = feasible) */
    public function validate(array $d): array
    {
        $e = [];
        $format = $d['format'] ?? null;
        $min = (int) ($d['min_participants'] ?? 0);
        $max = (int) ($d['max_participants'] ?? 0);

        $floor = self::MIN_PLAYERS[$format] ?? 2;
        $ceiling = $format === 'round_robin' ? 32 : 128;

        if ($min < $floor) {
            $e['min_participants'][] = self::FORMATS[$format] . " needs at least {$floor} participants.";
        }
        if ($max < $min) {
            $e['max_participants'][] = 'Maximum participants cannot be lower than the minimum.';
        }
        if ($max > $ceiling) {
            $e['max_participants'][] = $format === 'round_robin'
                ? "Round robin plays every pair, so it is limited to {$ceiling} participants."
                : "The system supports at most {$ceiling} participants per tournament.";
        }

        if ($format === 'swiss') {
            $rounds = (int) ($d['swiss_rounds'] ?? 0);
            if ($rounds < 1) {
                $e['swiss_rounds'][] = 'Swiss tournaments need at least 1 round.';
            } elseif ($rounds > $min - 1) {
                $e['swiss_rounds'][] = "{$rounds} Swiss rounds is infeasible: with a minimum of {$min} players, "
                    . 'at most ' . ($min - 1) . ' rounds can be played without rematches.';
            }
        }

        $opens  = $this->date($d['registration_opens_at'] ?? null);
        $closes = $this->date($d['registration_closes_at'] ?? null);
        $starts = $this->date($d['starts_at'] ?? null);
        $ends   = $this->date($d['ends_at'] ?? null);

        if ($opens && $closes && $closes->lte($opens)) {
            $e['registration_closes_at'][] = 'Registration must close after it opens.';
        }
        if ($closes && $starts && $starts->lt($closes)) {
            $e['starts_at'][] = 'The tournament cannot start before registration closes.';
        }
        if ($starts && $ends && $ends->lte($starts)) {
            $e['ends_at'][] = 'The schedule window must end after it starts.';
        }

        return $e;
    }

    public function estimatedMatches(string $format, int $players, ?int $swissRounds = null): int
    {
        return match ($format) {
            'single_elimination' => max(0, $players - 1),
            'double_elimination' => max(0, 2 * $players - 2),
            'round_robin'        => intdiv($players * ($players - 1), 2),
            'swiss'              => ($swissRounds ?? 0) * intdiv($players, 2),
            default              => 0,
        };
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}