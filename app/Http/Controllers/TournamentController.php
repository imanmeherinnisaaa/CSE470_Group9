<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentRequest;
use App\Models\Tournament;
use App\Services\TournamentConfigurationService;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::latest()->paginate(10);

        return view('tournaments.index', compact('tournaments'));
    }

    public function create()
    {
        $tournament = new Tournament([
            'format' => 'single_elimination',
            'min_participants' => 4,
            'max_participants' => 16,
            'best_of' => 1,
        ]);

        return view('tournaments.create', compact('tournament'));
    }

    public function store(TournamentRequest $request)
    {
        $data = $request->validated();

        $data['organizer_id'] = auth()->id();
        $data['status'] = 'draft';

        if ($data['format'] !== 'swiss') {
            $data['swiss_rounds'] = null;
        }

        $tournament = Tournament::create($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tournament created.');
    }

    public function show(
        Tournament $tournament,
        TournamentConfigurationService $config
    ) {
        $estimate = $config->estimatedMatches(
            $tournament->format,
            $tournament->max_participants,
            $tournament->swiss_rounds
        );

        // Get all confirmed participants.
        $confirmed = $tournament->entries()
            ->confirmed()
            ->with(['user', 'team'])
            ->orderBy('entered_at')
            ->get();

        // Get all waitlisted participants.
        $waitlist = $tournament->entries()
            ->waitlisted()
            ->with(['user', 'team'])
            ->orderBy('entered_at')
            ->orderBy('id')
            ->get();

        return view('tournaments.show', compact(
            'tournament',
            'estimate',
            'confirmed',
            'waitlist'
        ));
    }

    public function edit(Tournament $tournament)
    {
        $this->ensureEditable($tournament);

        return view('tournaments.edit', compact('tournament'));
    }

    public function update(
        TournamentRequest $request,
        Tournament $tournament
    ) {
        $this->ensureEditable($tournament);

        $data = $request->validated();

        if ($data['format'] !== 'swiss') {
            $data['swiss_rounds'] = null;
        }

        $tournament->update($data);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tournament updated.');
    }

    public function destroy(Tournament $tournament)
    {
        abort_unless(
            $tournament->organizer_id === auth()->id(),
            403
        );

        $tournament->delete();

        return redirect()
            ->route('tournaments.index')
            ->with('status', 'Tournament deleted.');
    }

    private function ensureEditable(Tournament $tournament): void
    {
        abort_unless(
            $tournament->organizer_id === auth()->id(),
            403
        );

        abort_if(
            $tournament->isLocked(),
            403,
            'Bracket already generated; settings are locked.'
        );
    }
}
