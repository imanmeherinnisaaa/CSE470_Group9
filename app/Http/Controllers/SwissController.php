<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Services\SwissPairingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SwissController extends Controller
{
    private function ownedTournament($id): Tournament
    {
        return Tournament::where('organizer_id', auth()->id())
            ->where('format', 'swiss')
            ->findOrFail($id);
    }

    private function standings(Tournament $tournament, $entries): array
    {
        $table = [];

        foreach ($entries as $entry) {
            $table[$entry->id] = [
                'id' => $entry->id,
                'name' => $entry->display_name,
                'seed' => $entry->seed,
                'score' => 0,
                'opponents' => [],
                'has_received_bye' => false,
            ];
        }

        $matches = $tournament->matches()
            ->where('bracket', 'swiss')
            ->orderBy('id')
            ->get();

        foreach ($matches as $match) {
            if ($match->is_bye && $match->entry1_id) {
                if (isset($table[$match->entry1_id])) {
                    $table[$match->entry1_id]['score'] += 1;
                    $table[$match->entry1_id]['has_received_bye'] = true;
                }

                continue;
            }

            if (
                $match->status !== 'completed' ||
                !$match->entry1_id ||
                !$match->entry2_id
            ) {
                continue;
            }

            if (
                !isset($table[$match->entry1_id]) ||
                !isset($table[$match->entry2_id])
            ) {
                continue;
            }

            $table[$match->entry1_id]['opponents'][] = $match->entry2_id;
            $table[$match->entry2_id]['opponents'][] = $match->entry1_id;

           if (
               $match->winner_entry_id &&
               isset($table[$match->winner_entry_id])
        ) {
               $table[$match->winner_entry_id]['score'] += 1;
        }
        }

        $standings = array_values($table);

        usort($standings, function ($a, $b) {
            return ($b['score'] <=> $a['score'])
                ?: (($a['seed'] ?? PHP_INT_MAX) <=> ($b['seed'] ?? PHP_INT_MAX))
                ?: ($a['id'] <=> $b['id']);
        });

        return $standings;
    }

    public function index()
    {
        $tournaments = Tournament::where('organizer_id', auth()->id())
            ->where('format', 'swiss')
            ->orderBy('name')
            ->get();

        $selectedId = request(
            'tournament_id',
            session('swiss_tournament_id')
        );

        $tournament = $tournaments->first(
            fn ($item) => (int) $item->id === (int) $selectedId
        );

        $entries = $tournament
            ? $tournament->entries()
                ->confirmed()
                ->with(['user', 'team'])
                ->orderByRaw('seed IS NULL')
                ->orderBy('seed')
                ->orderBy('id')
                ->get()
            : collect();

        $standings = $tournament
            ? $this->standings($tournament, $entries)
            : [];

        $rounds = $tournament
            ? $tournament->rounds()
                ->where('bracket', 'swiss')
                ->with([
                    'matches.entry1.user',
                    'matches.entry1.team',
                    'matches.entry2.user',
                    'matches.entry2.team',
                ])
                ->orderBy('number')
                ->get()
            : collect();

        $nextRound = $rounds->max('number') + 1;

        return view('swiss.index', compact(
            'tournaments',
            'tournament',
            'entries',
            'standings',
            'rounds',
            'nextRound'
        ));
    }

    public function generate(SwissPairingService $service)
    {
        $request = request();

        $validated = $request->validate([
            'tournament_id' => 'required|integer',
        ]);

        $tournament = $this->ownedTournament($validated['tournament_id']);

        $entries = $tournament->entries()
            ->confirmed()
            ->with(['user', 'team'])
            ->orderByRaw('seed IS NULL')
            ->orderBy('seed')
            ->orderBy('id')
            ->get();

        if ($entries->count() < 2) {
            return back()->withErrors([
                'pairing' => 'At least two confirmed entries are required.',
            ]);
        }

        $rounds = $tournament->rounds()
            ->where('bracket', 'swiss')
            ->orderByDesc('number')
            ->get();

        $nextRound = ($rounds->first()?->number ?? 0) + 1;

        $maximumRounds = (int) ($tournament->swiss_rounds ?: 0);

        if ($maximumRounds > 0 && $nextRound > $maximumRounds) {
            return back()->withErrors([
                'pairing' => 'All configured Swiss rounds have already been generated.',
            ]);
        }

        if ($rounds->isNotEmpty()) {
            $previousRound = $rounds->first();

            $unfinished = $previousRound->matches()
                ->whereNotIn('status', ['completed', 'bye'])
                ->exists();

            if ($unfinished) {
                return back()->withErrors([
                    'pairing' => 'Complete all matches in the previous round before generating another round.',
                ]);
            }
        }

        $participants = $this->standings($tournament, $entries);

        try {
            $result = $service->generatePairings($participants);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'pairing' => $exception->getMessage(),
            ]);
        }

        DB::transaction(function () use (
            $tournament,
            $result,
            $nextRound
        ) {
            $round = Round::create([
                'tournament_id' => $tournament->id,
                'number' => $nextRound,
                'name' => 'Swiss Round ' . $nextRound,
                'bracket' => 'swiss',
            ]);

            $position = 1;

            foreach ($result['matches'] as $pairing) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_id' => $round->id,
                    'match_number' => $position,
                    'bracket' => 'swiss',
                    'position' => $position,
                    'entry1_id' => $pairing['player1']['id'],
                    'entry2_id' => $pairing['player2']['id'],
                    'status' => 'ready',
                    'is_bye' => false,
                ]);

                $position++;
            }

            if ($result['bye'] !== null) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_id' => $round->id,
                    'match_number' => $position,
                    'bracket' => 'swiss',
                    'position' => $position,
                    'entry1_id' => $result['bye']['id'],
                    'entry2_id' => null,
                    'status' => 'bye',
                    'is_bye' => true,
                    'winner_entry_id' => $result['bye']['id'],
                ]);
            }
        });

        session(['swiss_tournament_id' => $tournament->id]);

        return redirect()->route('swiss.demo', [
            'tournament_id' => $tournament->id,
        ])->with('status', 'Swiss Round ' . $nextRound . ' pairings saved successfully.');
    }

    public function updateScores(Request $request)
    {
        return back()->with(
            'status',
            'Submit match scores through the match result reporting page.'
        );
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer',
        ]);

        $tournament = $this->ownedTournament($validated['tournament_id']);

        $matches = $tournament->matches()->where('bracket', 'swiss');

        if ($matches->whereHas('reports')->exists()) {
            return back()->withErrors([
                'pairing' => 'Swiss rounds cannot be reset after result reports have been submitted.',
            ]);
        }

        DB::transaction(function () use ($tournament) {
            $tournament->rounds()
                ->where('bracket', 'swiss')
                ->delete();
        });

        return redirect()->route('swiss.demo', [
            'tournament_id' => $tournament->id,
        ])->with('status', 'Swiss rounds reset successfully.');
    }
}
