<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\GameMatch;
use App\Models\TimeSlot;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use App\Models\Station;

class ScheduleController extends Controller
{
    /**
     * Display the tournament schedule.
     */
    public function index(Tournament $tournament)
    {
        abort_unless($tournament->organizer_id === auth()->id(), 403);

        $matches = $tournament->matches()
            ->with(['round', 'entry1', 'entry2', 'timeSlot.station'])
            ->orderBy('round_id')
            ->orderBy('position')
            ->get();

        $timeSlots = $tournament->timeSlots()
            ->with('station')
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();
        $stations = $tournament->stations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('schedule.index', compact(
            'tournament',
            'matches',
            'timeSlots',
            'stations'
));
    }

    /**
     * Assign a match to a time slot.
     */
    public function assign(
        Request $request,
        Tournament $tournament,
        SchedulingService $schedulingService
    ) {
        abort_unless($tournament->organizer_id === auth()->id(), 403);

        $validated = $request->validate([
            'match_id' => ['required', 'integer', 'exists:game_matches,id'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
        ]);

        $match = $tournament->matches()
            ->findOrFail($validated['match_id']);

        $timeSlot = $tournament->timeSlots()
            ->findOrFail($validated['time_slot_id']);

        $schedulingService->assignMatch($match, $timeSlot);

        return redirect()
            ->route('schedule.index', $tournament)
            ->with('success', 'Match scheduled successfully.');
    }
    
    /**
     * Create a station for the tournament.
     */
    public function storeStation(Request $request, Tournament $tournament)
    {
        abort_unless($tournament->organizer_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $tournament->stations()->create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('schedule.index', $tournament)
            ->with('success', 'Station created successfully.');
    }

    /**
     * Create a time slot for the tournament.
     */
    public function storeTimeSlot(Request $request, Tournament $tournament)
    {
        abort_unless($tournament->organizer_id === auth()->id(), 403);

        $validated = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $station = $tournament->stations()
            ->where('is_active', true)
            ->findOrFail($validated['station_id']);

        $tournament->timeSlots()->create([
            'station_id' => $station->id,
            'slot_date' => $validated['slot_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return redirect()
            ->route('schedule.index', $tournament)
            ->with('success', 'Time slot created successfully.');
    }


    }
