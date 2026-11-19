<?php

namespace App\Http\Controllers;

use App\Exceptions\RosterException;
use App\Http\Requests\AddPlayerRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\SubstituteRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\RosterSlot;
use App\Models\Team;
use App\Services\RosterService;

class RosterController extends Controller
{
    public function __construct(private RosterService $rosters)
    {
    }

    /** Shortcut for a captain: go to their roster, or to the create form if they have no team. */
    public function mine()
    {
        $team = Team::where('captain_id', auth()->id())->first();

        return $team
            ? redirect()->route('teams.roster.show', $team)
            : redirect()->route('teams.create');
    }

    public function create()
    {
        return view('rosters.create');
    }

    public function store(StoreTeamRequest $request)
    {
        try {
            $team = $this->rosters->createTeam($request->user(), $request->validated('name'));
        } catch (RosterException $e) {
            return back()->withErrors(['roster' => $e->getMessage()])->withInput();
        }

        return redirect()->route('teams.roster.show', $team)
            ->with('status', 'Team created. You are the captain and the first starter.');
    }

    /** Roster page. Anyone can read it; only the captain sees the edit controls. */
    public function show(Team $team)
    {
        $this->rosters->syncLocks($team);
        $team->load(['captain', 'rosterSlots.player']);

        return view('rosters.show', [
            'team' => $team,
            'starters' => $team->rosterSlots->where('is_substitute', false)->values(),
            'substitutes' => $team->rosterSlots->where('is_substitute', true)->values(),
            'limits' => $this->rosters->limits($team),
            'locked' => $team->isLocked(),
            'isCaptain' => auth()->check() && auth()->user()->can('manage', $team),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        return $this->attempt(
            fn () => $this->rosters->renameTeam($team, $request->validated('name')),
            'Team renamed.'
        );
    }

    public function destroy(Team $team)
    {
        abort_unless(auth()->user()->can('manage', $team), 403);

        try {
            $this->rosters->deleteTeam($team);
        } catch (RosterException $e) {
            return back()->withErrors(['roster' => $e->getMessage()]);
        }

        return redirect()->route('teams.mine')->with('status', 'Team deleted.');
    }

    public function addPlayer(AddPlayerRequest $request, Team $team)
    {
        return $this->attempt(
            fn () => $this->rosters->addPlayer($team, $request->validated('email'), $request->validated('slot_type') === 'substitute'),
            'Player added to the roster.'
        );
    }

    public function removePlayer(Team $team, RosterSlot $slot)
    {
        abort_unless(auth()->user()->can('manage', $team), 403);
        abort_unless((int) $slot->team_id === (int) $team->id, 404);

        return $this->attempt(fn () => $this->rosters->removePlayer($team, $slot), 'Player removed from the roster.');
    }

    public function substitute(SubstituteRequest $request, Team $team)
    {
        return $this->attempt(
            fn () => $this->rosters->substitute($team, (int) $request->validated('starter_slot_id'), (int) $request->validated('substitute_slot_id')),
            'Substitution made.'
        );
    }

    /** Runs a roster action and turns a rule violation into a plain-language error on the page. */
    private function attempt(callable $action, string $success)
    {
        try {
            $action();
        } catch (RosterException $e) {
            return back()->withErrors(['roster' => $e->getMessage()])->withInput();
        }

        return back()->with('status', $success);
    }
}
