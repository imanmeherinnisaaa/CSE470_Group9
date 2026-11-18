<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\MatchReport;
use App\Services\BracketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    public function create(GameMatch $gameMatch)
    {
        $gameMatch->load(['entry1.team', 'entry1.user', 'entry2.team', 'entry2.user', 'reports']);

        abort_unless(
            $this->canReport($gameMatch->entry1) ||
            $this->canReport($gameMatch->entry2),
            403
        );

        return view('results.create', compact('gameMatch'));
    }

    public function store(
        Request $request,
        GameMatch $gameMatch,
        BracketService $bracketService
    ) {
        $validated = $request->validate([
            'score1' => ['required', 'integer', 'min:0'],
            'score2' => ['required', 'integer', 'min:0'],
        ]);

        if ($validated['score1'] === $validated['score2']) {
            throw ValidationException::withMessages([
                'score2' => 'A match must have a winner. Scores cannot be equal.',
            ]);
        }

        return DB::transaction(function () use (
            $gameMatch,
            $validated,
            $bracketService
        ) {
            $match = GameMatch::with(['entry1.team', 'entry2.team'])
                ->lockForUpdate()
                ->findOrFail($gameMatch->id);

            abort_unless(
                $this->canReport($match->entry1) ||
                $this->canReport($match->entry2),
                403
            );

            if ($match->status !== 'ready') {
                throw ValidationException::withMessages([
                    'match' => 'This match is not accepting result reports.',
                ]);
            }

            if ($match->reports()->where('reporter_id', auth()->id())->exists()) {
                throw ValidationException::withMessages([
                    'match' => 'You have already reported this match.',
                ]);
            }

            MatchReport::create([
                'game_match_id' => $match->id,
                'reporter_id' => auth()->id(),
                'score1' => $validated['score1'],
                'score2' => $validated['score2'],
            ]);

            $reports = $match->reports()->get();

            if ($reports->count() === 2) {
                $first = $reports[0];
                $second = $reports[1];

                if (
                    $first->score1 !== $second->score1 ||
                    $first->score2 !== $second->score2
                ) {
                    $match->update(['status' => 'conflicting']);

                    return redirect()
                        ->route('matches.result.create', $match)
                        ->with('error', 'The reports disagree. The match is marked as conflicting.');
                }

                $winnerId = $first->score1 > $first->score2
                    ? $match->entry1_id
                    : $match->entry2_id;

                $match->update([
                    'score1' => $first->score1,
                    'score2' => $first->score2,
                ]);

                $bracketService->advanceWinner($match->fresh(), $winnerId);

                return redirect()
                    ->route('matches.result.create', $match)
                    ->with('success', 'Both reports agree! The result is confirmed and the winner advances.');
            }

            return redirect()
                ->route('matches.result.create', $match)
                ->with('success', 'Your report was submitted. Waiting for the other participant to confirm.');
        });
    }

    private function canReport($entry): bool
    {
        if (!$entry) {
            return false;
        }

        if ($entry->user_id) {
            return (int) $entry->user_id === (int) auth()->id();
        }

        return $entry->team &&
            (int) $entry->team->captain_id === (int) auth()->id();
    }
}
