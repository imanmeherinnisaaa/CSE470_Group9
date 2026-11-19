<?php

namespace App\Http\Controllers;

use App\Exceptions\EntryException;
use App\Http\Requests\StoreEntryRequest;
use App\Models\Entry;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\EntryService;

class EntryController extends Controller
{
    public function __construct(private EntryService $entries)
    {
    }

    /** Entry page: tournament summary, entrants, waitlist, and the enter/withdraw action. */
    public function show(Tournament $tournament)
    {
        $user = auth()->user();

        return view('entries.show', [
            'tournament' => $tournament,
            'confirmed' => $tournament->entries()->confirmed()->with(['user', 'team'])->orderBy('entered_at')->get(),
            'waitlist' => $tournament->entries()->waitlisted()->with(['user', 'team'])->orderBy('entered_at')->orderBy('id')->get(),
            'myEntry' => $user ? $this->entries->entryFor($tournament, $user) : null,
            'captainTeams' => ($user && $tournament->isTeamBased())
                ? Team::where('captain_id', $user->id)->get()
                : collect(),
        ]);
    }

    public function store(StoreEntryRequest $request, Tournament $tournament)
    {
        $team = $request->filled('team_id') ? Team::findOrFail($request->input('team_id')) : null;

        try {
            $entry = $this->entries->enter($tournament, $request->user(), $team);
        } catch (EntryException $e) {
            return back()->withErrors(['entry' => $e->getMessage()])->withInput();
        }

        $message = $entry->status === Entry::CONFIRMED
            ? 'You are in. Your spot is confirmed.'
            : 'The tournament is full. You are number ' . $entry->waitlistPosition() . ' on the waitlist.';

        return redirect()->route('tournaments.entry.show', $tournament)->with('status', $message);
    }

    public function destroy(Entry $entry)
    {
        abort_unless(auth()->user()->can('withdraw', $entry), 403);

        try {
            $this->entries->withdraw($entry);
        } catch (EntryException $e) {
            return back()->withErrors(['entry' => $e->getMessage()]);
        }

        return redirect()->route('tournaments.entry.show', $entry->tournament_id)
            ->with('status', 'You have withdrawn from the tournament.');
    }
}
