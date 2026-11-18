<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BracketService
{
    private int $matchNo = 0;

    public function generate(Tournament $tournament): void
    {
        if (!$tournament->isElimination()) {
            throw new InvalidArgumentException(
                'Bracket generation supports single and double elimination only. Round robin and Swiss use their own pairing engines.'
            );
        }
        if ($tournament->matches()->where('status', 'completed')->exists()) {
            throw new InvalidArgumentException('Cannot regenerate: some matches have already been played.');
        }

        $entries = $tournament->entries()
            ->where('status', Entry::CONFIRMED)
            ->orderByRaw('seed IS NULL')   // seeded entries first
            ->orderBy('seed')
            ->orderBy('id')
            ->get();

        $required = max($tournament->format === 'double_elimination' ? 4 : 2, $tournament->min_participants);
        if ($entries->count() < $required) {
            throw new InvalidArgumentException(
                "At least {$required} confirmed entries are required; this tournament has {$entries->count()}."
            );
        }

        DB::transaction(function () use ($tournament, $entries) {
            $this->matchNo = 0;
            $tournament->matches()->delete();
            $tournament->rounds()->delete();

            $ordered = $entries->pluck('id')->values()->all(); // index 0 = seed 1

            $tournament->format === 'double_elimination'
                ? $this->buildDouble($tournament, $ordered)
                : $this->buildSingle($tournament, $ordered);

            $tournament->update(['status' => 'in_progress', 'bracket_generated_at' => now()]);
        });
    }

    /**
     * Called by F8 (Result Reporting) once both reports agree.
     * Records the winner and moves the winner and the loser along their paths.
     */
    public function advanceWinner(GameMatch $match, int $winnerEntryId): void
    {
        if ($match->status !== 'ready') {
            throw new InvalidArgumentException('Only a ready match can be completed.');
        }
        if (!in_array($winnerEntryId, [$match->entry1_id, $match->entry2_id], true)) {
            throw new InvalidArgumentException('The winner must be one of the two players in the match.');
        }

        $loserId = $match->entry1_id === $winnerEntryId ? $match->entry2_id : $match->entry1_id;

        DB::transaction(function () use ($match, $winnerEntryId, $loserId) {
            $match->update([
                'status' => 'completed',
                'winner_entry_id' => $winnerEntryId,
                'loser_entry_id' => $loserId,
            ]);
            $this->placeEntry($match->next_match_id, $match->next_slot, $winnerEntryId);
            $this->placeEntry($match->loser_next_match_id, $match->loser_next_slot, $loserId);
        });
    }

    // ---------------------------------------------------------------- builders

    private function buildSingle(Tournament $t, array $ordered): void
    {
        $size = $this->bracketSize(count($ordered));
        $k = (int) round(log($size, 2));

        $winners = $this->buildWinners($t, $k, 'Final');
        $this->seedFirstRound($winners[1], $size, $ordered);
    }

    private function buildDouble(Tournament $t, array $ordered): void
    {
        $size = max(4, $this->bracketSize(count($ordered)));
        $k = (int) round(log($size, 2));

        $wb = $this->buildWinners($t, $k, 'Winners Final');

        // Losers bracket: for each j there are two rounds (2j-1 and 2j) of 2^(k-j-1) matches
        $lb = [];
        for ($j = 1; $j <= $k - 1; $j++) {
            $count = 2 ** ($k - $j - 1);
            foreach ([1, 2] as $half) {
                $no = 2 * $j - 2 + $half;
                $lb[$no] = $this->makeMatches($t, $this->makeRound($t, $no, "Losers Round {$no}", 'losers'), $count);
            }
        }
        $lbLast = 2 * ($k - 1);
        Round::whereKey($lb[$lbLast][0]->round_id)->update(['name' => 'Losers Final']);

        $grandFinal = $this->makeMatches($t, $this->makeRound($t, 1, 'Grand Final', 'grand_final'), 1)[0];

        // Winner paths inside the losers bracket
        for ($no = 1; $no <= $lbLast; $no++) {
            foreach ($lb[$no] as $i => $match) {
                if ($no === $lbLast) {
                    $this->link($match, $grandFinal, 2);
                } elseif ($no % 2 === 1) {
                    $this->link($match, $lb[$no + 1][$i], 1);                      // same index, slot 1
                } else {
                    $this->link($match, $lb[$no + 1][intdiv($i, 2)], $i % 2 + 1);  // pairs merge
                }
            }
        }
        $this->link($wb[$k][0], $grandFinal, 1); // winners-bracket champion

        // Loser paths: winners-bracket losers drop into the losers bracket
        foreach ($wb[1] as $i => $match) {
            $this->linkLoser($match, $lb[1][intdiv($i, 2)], $i % 2 + 1);
        }
        for ($r = 2; $r <= $k; $r++) {
            foreach ($wb[$r] as $i => $match) {
                $this->linkLoser($match, $lb[2 * ($r - 1)][$i], 2);
            }
        }

        $this->seedFirstRound($wb[1], $size, $ordered);
    }

    /** Builds the winners bracket (or the whole single-elimination tree) and links the winner paths. */
    private function buildWinners(Tournament $t, int $k, string $finalName): array
    {
        $rounds = [];
        for ($r = 1; $r <= $k; $r++) {
            $name = match (true) {
                $r === $k     => $finalName,
                $r === $k - 1 => 'Semifinals',
                $r === $k - 2 => 'Quarterfinals',
                default       => "Round {$r}",
            };
            $rounds[$r] = $this->makeMatches($t, $this->makeRound($t, $r, $name, 'winners'), 2 ** ($k - $r));
        }
        for ($r = 1; $r < $k; $r++) {
            foreach ($rounds[$r] as $i => $match) {
                $this->link($match, $rounds[$r + 1][intdiv($i, 2)], $i % 2 + 1);
            }
        }

        return $rounds;
    }

    // ----------------------------------------------------------------- seeding

    private function seedFirstRound(array $firstRound, int $size, array $ordered): void
    {
        $order = $this->seedOrder($size);
        $n = count($ordered);

        foreach ($firstRound as $i => $match) {
            $data = [];
            foreach ([1 => $order[2 * $i], 2 => $order[2 * $i + 1]] as $slot => $seed) {
                if ($seed <= $n) {
                    $data["entry{$slot}_id"] = $ordered[$seed - 1];
                } else {
                    $data["slot{$slot}_empty"] = true; // the opponent gets a bye
                }
            }
            $match->update($data);
        }
        foreach ($firstRound as $match) {
            $this->settle($match);
        }
    }

    /** Standard seed order: size 8 gives [1,8,4,5,2,7,3,6]. */
    private function seedOrder(int $size): array
    {
        $order = [1];
        while (count($order) < $size) {
            $len = count($order);
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = 2 * $len + 1 - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    private function bracketSize(int $n): int
    {
        $size = 1;
        while ($size < $n) {
            $size <<= 1;
        }

        return $size;
    }

    // ------------------------------------------------------- bye / void logic

    private function settle(GameMatch $match): void
    {
        $m = $match->fresh();
        if (in_array($m->status, ['completed', 'bye', 'void'], true)) {
            return;
        }

        $e1 = $m->entry1_id;
        $e2 = $m->entry2_id;

        if ($m->slot1_empty && $m->slot2_empty) {
            // Nobody will ever play here: pass "emptiness" downstream
            $m->update(['status' => 'void']);
            $this->emptyTarget($m->next_match_id, $m->next_slot);
            $this->emptyTarget($m->loser_next_match_id, $m->loser_next_slot);
        } elseif (($m->slot1_empty && $e2) || ($m->slot2_empty && $e1)) {
            // Exactly one real player: automatic bye
            $winner = $e1 ?: $e2;
            $m->update(['status' => 'bye', 'is_bye' => true, 'winner_entry_id' => $winner]);
            $this->placeEntry($m->next_match_id, $m->next_slot, $winner);
            $this->emptyTarget($m->loser_next_match_id, $m->loser_next_slot); // a bye has no loser
        } elseif ($e1 && $e2) {
            $m->update(['status' => 'ready']);
        }
    }

    private function placeEntry(?int $matchId, ?int $slot, ?int $entryId): void
    {
        if (!$matchId || !$entryId) {
            return; // end of the path: eliminated or champion
        }
        $target = GameMatch::findOrFail($matchId);
        $target->update(["entry{$slot}_id" => $entryId]);
        $this->settle($target);
    }

    private function emptyTarget(?int $matchId, ?int $slot): void
    {
        if (!$matchId) {
            return;
        }
        $target = GameMatch::findOrFail($matchId);
        $target->update(["slot{$slot}_empty" => true]);
        $this->settle($target);
    }

    // ----------------------------------------------------------------- helpers

    private function makeRound(Tournament $t, int $number, string $name, string $bracket): Round
    {
        return Round::create([
            'tournament_id' => $t->id,
            'number' => $number,
            'name' => $name,
            'bracket' => $bracket,
        ]);
    }

    /** @return GameMatch[] */
    private function makeMatches(Tournament $t, Round $round, int $count): array
    {
        $matches = [];
        for ($i = 0; $i < $count; $i++) {
            $matches[] = GameMatch::create([
                'tournament_id' => $t->id,
                'round_id' => $round->id,
                'match_number' => ++$this->matchNo,
                'bracket' => $round->bracket,
                'position' => $i,
            ]);
        }

        return $matches;
    }

    private function link(GameMatch $from, GameMatch $to, int $slot): void
    {
        $from->update(['next_match_id' => $to->id, 'next_slot' => $slot]);
    }

    private function linkLoser(GameMatch $from, GameMatch $to, int $slot): void
    {
        $from->update(['loser_next_match_id' => $to->id, 'loser_next_slot' => $slot]);
    }
}