<x-app-layout>
<div class="container py-4">
    <h1 class="h3 mb-1">{{ $tournament->name }}</h1>
    <p class="text-muted mb-4">
        {{ $tournament->isTeamBased() ? 'Team entry' : 'Solo entry' }}.
        {{ $confirmed->count() }}{{ $tournament->max_participants ? ' of ' . $tournament->max_participants : '' }} spots confirmed,
        {{ $waitlist->count() }} on the waitlist.
        @if ($tournament->entry_opens_at || $tournament->entry_closes_at)
            Entry window: {{ $tournament->entry_opens_at?->format('d M Y H:i') ?? 'now' }} to {{ $tournament->entry_closes_at?->format('d M Y H:i') ?? 'no end date' }}.
        @endif
        @if ($tournament->min_rating !== null || $tournament->max_rating !== null)
            Rating range: {{ $tournament->min_rating ?? 'any' }} to {{ $tournament->max_rating ?? 'any' }}.
        @endif
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('entry')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    @error('team_id')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    {{-- Enter / withdraw --}}
    <div class="card mb-4">
        <div class="card-body">
            @guest
                <p class="mb-0">Log in as a player to enter this tournament. <a href="{{ route('login') }}">Log in</a></p>
            @else
                @if ($myEntry && $myEntry->isActive())
                    <p class="mb-2">
                        @if ($myEntry->status === 'confirmed')
                            <span class="badge bg-success">Confirmed</span> {{ $myEntry->display_name }} is in this tournament.
                        @else
                            <span class="badge bg-warning text-dark">Waitlisted</span>
                            {{ $myEntry->display_name }} is number {{ $myEntry->waitlistPosition() }} on the waitlist. You move up automatically when a spot opens.
                        @endif
                    </p>
                    @if ($tournament->isRegistrationOpen())
                        <form method="POST" action="{{ route('entries.destroy', $myEntry) }}"
                              onsubmit="return confirm('Withdraw from this tournament?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">Withdraw</button>
                        </form>
                    @endif
                @elseif ($myEntry && $myEntry->status === 'disqualified')
                    <p class="mb-0"><span class="badge bg-danger">Disqualified</span> This entrant cannot enter again.</p>
                @elseif ($tournament->isEntryWindowOpen())
                    <form method="POST" action="{{ route('tournaments.entry.store', $tournament) }}" class="row g-2 align-items-end">
                        @csrf
                        @if ($tournament->isTeamBased())
                            @if ($captainTeams->isEmpty())
                                <p class="mb-0">You need to be a team captain to enter a team tournament.</p>
                            @else
                                <div class="col-auto">
                                    <label for="team_id" class="form-label">Team</label>
                                    <select name="team_id" id="team_id" class="form-select" required>
                                        @foreach ($captainTeams as $team)
                                            <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                        @if (! $tournament->isTeamBased() || $captainTeams->isNotEmpty())
                            <div class="col-auto">
                                <button class="btn btn-primary">
                                    {{ $tournament->hasOpenSlot() ? 'Enter tournament' : 'Join waitlist' }}
                                </button>
                            </div>
                        @endif
                    </form>
                @else
                    <p class="mb-0">Entry is closed for this tournament.</p>
                @endif
            @endguest
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <h2 class="h5">Confirmed entrants</h2>
            @forelse ($confirmed as $entry)
                @if ($loop->first)
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>Rating</th></tr></thead>
                    <tbody>
                @endif
                        <tr><td>{{ $entry->display_name }}</td><td>{{ $entry->rating_snapshot }}</td></tr>
                @if ($loop->last)
                    </tbody>
                </table>
                @endif
            @empty
                <p class="text-muted">Nobody has entered yet.</p>
            @endforelse
        </div>

        <div class="col-md-6 mb-4">
            <h2 class="h5">Waitlist</h2>
            @forelse ($waitlist as $entry)
                @if ($loop->first)
                <table class="table table-sm">
                    <thead><tr><th>#</th><th>Name</th><th>Rating</th></tr></thead>
                    <tbody>
                @endif
                        <tr><td>{{ $loop->iteration }}</td><td>{{ $entry->display_name }}</td><td>{{ $entry->rating_snapshot }}</td></tr>
                @if ($loop->last)
                    </tbody>
                </table>
                @endif
            @empty
                <p class="text-muted">The waitlist is empty.</p>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
