<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\BracketService;
use InvalidArgumentException;

class BracketController extends Controller
{
    public function show(Tournament $tournament)
    {
        $rounds = $tournament->rounds()
            ->with(['matches.entry1.user', 'matches.entry1.team', 'matches.entry2.user', 'matches.entry2.team'])
            ->orderBy('number')
            ->get()
            ->groupBy('bracket');

        return view('brackets.show', compact('tournament', 'rounds'));
    }

    public function generate(Tournament $tournament, BracketService $service)
    {
        abort_unless($tournament->organizer_id === auth()->id(), 403);

        try {
            $service->generate($tournament);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['bracket' => $e->getMessage()]);
        }

        return redirect()->route('bracket.show', $tournament)->with('status', 'Bracket generated.');
    }
}