<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Entry;
use App\Models\Tournament;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Display a player's availability for a tournament.
     */
    public function index(Tournament $tournament)
    {
        $this->ensureParticipant($tournament);

        $availabilities = Availability::where('tournament_id', $tournament->id)
            ->where('user_id', auth()->id())
            ->orderBy('available_date')
            ->orderBy('start_time')
            ->get();

        return view('availability.index', compact(
            'tournament',
            'availabilities'
        ));
    }

    /**
     * Store a player's availability.
     */
    public function store(Request $request, Tournament $tournament)
    {
        $this->ensureParticipant($tournament);

        $validated = $request->validate([
            'available_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        Availability::create([
            'user_id' => auth()->id(),
            'tournament_id' => $tournament->id,
            'available_date' => $validated['available_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return redirect()
            ->route('availability.index', $tournament)
            ->with('success', 'Your availability was submitted successfully.');
    }

    /**
     * Ensure that the logged-in user is a confirmed tournament participant.
     */
    private function ensureParticipant(Tournament $tournament): void
    {
        $isParticipant = Entry::where('tournament_id', $tournament->id)
            ->where('status', Entry::CONFIRMED)
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('team.rosterSlots', function ($rosterQuery) {
                        $rosterQuery->where('player_id', auth()->id())
                            ->where('is_substitute', false);
                    });
            })
            ->exists();

        abort_unless($isParticipant, 403);
    }
}
