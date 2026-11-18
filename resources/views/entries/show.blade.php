<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $tournament->name }} · Entry</h2></x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status')) <div class="p-3 bg-green-100 rounded">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="p-3 bg-red-100 text-red-800 rounded">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @php([$opens, $closes] = $tournament->entryWindow())
        <div class="bg-white shadow rounded p-6 grid grid-cols-2 gap-3 text-sm">
            <div><b>Entry type:</b> {{ $tournament->isTeamBased() ? 'Team' : 'Solo' }}</div>
            <div><b>Status:</b> {{ $tournament->status }}</div>
            <div><b>Confirmed:</b> {{ $confirmed->count() }}{{ $tournament->max_participants ? ' of ' . $tournament->max_participants : '' }}</div>
            <div><b>Waitlist:</b> {{ $waitlist->count() }}</div>
            <div><b>Entry window:</b> {{ $opens?->format('d M Y H:i') ?? 'open now' }} → {{ $closes?->format('d M Y H:i') ?? 'no end date' }}</div>
            <div><b>Rating range:</b> {{ $tournament->min_rating ?? 'any' }} – {{ $tournament->max_rating ?? 'any' }}</div>
            @if ($tournament->isTeamBased())
                <div class="col-span-2"><b>Starters per team:</b> {{ $tournament->team_size ?? 'not set' }}</div>
            @endif
        </div>

        {{-- Enter / withdraw --}}
        <div class="bg-white shadow rounded p-6">
            @guest
                <p>Log in as a player to enter this tournament. <a class="underline" href="{{ route('login') }}">Log in</a></p>
            @else
                @if ($myEntry && $myEntry->isActive())
                    <p class="mb-3">
                        @if ($myEntry->status === 'confirmed')
                            <span class="px-2 py-1 text-xs bg-green-600 text-white rounded">Confirmed</span>
                            {{ $myEntry->display_name }} is in this tournament.
                        @else
                            <span class="px-2 py-1 text-xs bg-yellow-400 rounded">Waitlisted</span>
                            {{ $myEntry->display_name }} is number {{ $myEntry->waitlistPosition() }} on the waitlist. You move up automatically when a spot opens.
                        @endif
                    </p>
                    @if ($tournament->isRegistrationOpen())
                        <form method="POST" action="{{ route('entries.destroy', $myEntry) }}" onsubmit="return confirm('Withdraw from this tournament?');">
                            @csrf
                            @method('DELETE')
                            <button class="px-4 py-2 bg-red-600 text-white rounded">Withdraw</button>
                        </form>
                    @endif
                @elseif ($myEntry && $myEntry->status === 'disqualified')
                    <p><span class="px-2 py-1 text-xs bg-red-600 text-white rounded">Disqualified</span> This entrant cannot enter again.</p>
                @elseif ($tournament->isEntryWindowOpen())
                    <form method="POST" action="{{ route('tournaments.entry.store', $tournament) }}" class="flex items-end gap-3">
                        @csrf
                        @if ($tournament->isTeamBased())
                            @if ($captainTeams->isEmpty())
                                <p>You need to be a team captain to enter a team tournament. <a class="underline" href="{{ route('teams.mine') }}">Create or open your team</a></p>
                            @else
                                <div>
                                    <label for="team_id" class="block text-sm mb-1">Team</label>
                                    <select name="team_id" id="team_id" class="border-gray-300 rounded" required>
                                        @foreach ($captainTeams as $team)
                                            <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                        @if (! $tournament->isTeamBased() || $captainTeams->isNotEmpty())
                            <button class="px-4 py-2 bg-gray-800 text-white rounded">
                                {{ $tournament->hasOpenSlot() ? 'Enter tournament' : 'Join waitlist' }}
                            </button>
                        @endif
                    </form>
                @else
                    <p>Entry is closed for this tournament.</p>
                @endif
            @endguest
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white shadow rounded p-6">
                <h3 class="font-semibold mb-2">Confirmed entrants</h3>
                @forelse ($confirmed as $entry)
                    @if ($loop->first)<table class="w-full text-sm"><thead><tr class="text-left"><th>Name</th><th>Rating</th></tr></thead><tbody>@endif
                    <tr class="border-t"><td class="py-1">{{ $entry->display_name }}</td><td>{{ $entry->rating_snapshot }}</td></tr>
                    @if ($loop->last)</tbody></table>@endif
                @empty
                    <p class="text-gray-500 text-sm">Nobody has entered yet.</p>
                @endforelse
            </div>
            <div class="bg-white shadow rounded p-6">
                <h3 class="font-semibold mb-2">Waitlist</h3>
                @forelse ($waitlist as $entry)
                    @if ($loop->first)<table class="w-full text-sm"><thead><tr class="text-left"><th>#</th><th>Name</th><th>Rating</th></tr></thead><tbody>@endif
                    <tr class="border-t"><td class="py-1">{{ $loop->iteration }}</td><td>{{ $entry->display_name }}</td><td>{{ $entry->rating_snapshot }}</td></tr>
                    @if ($loop->last)</tbody></table>@endif
                @empty
                    <p class="text-gray-500 text-sm">The waitlist is empty.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
