<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Join Tournament — {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6 space-y-6">

        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white p-6 rounded shadow space-y-2">
            <h3 class="text-lg font-bold">{{ $tournament->name }}</h3>
            <p>Game: {{ $tournament->game }}</p>
            <p>Format: {{ $tournament->formatLabel() }}</p>
            <p>Status: {{ $tournament->status }}</p>
            <p>
                Participants:
                {{ $confirmed->count() }} / {{ $tournament->max_participants }}
            </p>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-bold text-lg mb-4">Tournament Registration</h3>

            @auth

                @if (in_array(auth()->user()->role, ['player', 'team_captain'], true))

                    @if ($myEntry && $myEntry->isActive())

                        <p class="text-green-700 font-semibold">
                            You have already entered this tournament.
                        </p>

                        <p class="mt-2">
                            Entry status: {{ ucfirst($myEntry->status) }}
                        </p>

                        @if ($myEntry->status === 'confirmed')
                            <a href="{{ route('availability.index', $tournament) }}"
                               class="inline-block mt-4 bg-green-700 text-white px-4 py-2 rounded">
                                Submit Availability
                            </a>
                        @endif

                        @if ($tournament->isRegistrationOpen())
                            <form method="POST"
                                  action="{{ route('entries.destroy', $myEntry) }}"
                                  class="mt-4"
                                  onsubmit="return confirm('Withdraw from this tournament?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="bg-red-600 text-white px-4 py-2 rounded">
                                    Withdraw Entry
                                </button>
                            </form>
                        @endif

                    @else

                        @if ($tournament->isTeamBased() && $captainTeams->isEmpty())
                            <p class="text-red-600">
                                You need to create a team and become its captain first.
                            </p>
                        @else

                            <form method="POST"
                                  action="{{ route('tournaments.entry.store', $tournament) }}">
                                @csrf

                                @if ($tournament->isTeamBased())
                                    <label class="block mb-2 font-semibold">
                                        Select Your Team
                                    </label>

                                    <select name="team_id"
                                            required
                                            class="border rounded p-2 w-full mb-4">
                                        <option value="">Choose a team</option>

                                        @foreach ($captainTeams as $team)
                                            <option value="{{ $team->id }}">
                                                {{ $team->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded">
                                    Enter Tournament
                                </button>
                            </form>

                        @endif

                    @endif

                @else

                    <p class="text-red-600">
                        Only players and team captains can enter tournaments.
                    </p>

                @endif

            @else

                <p>Please log in to enter this tournament.</p>

            @endauth
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-bold text-lg mb-3">Confirmed Participants</h3>

            @forelse ($confirmed as $entry)
                <p class="border-b py-2">
                    {{ $entry->display_name }}
                </p>
            @empty
                <p>No participants have joined yet.</p>
            @endforelse
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-bold text-lg mb-3">Waitlist</h3>

            @forelse ($waitlist as $entry)
                <p class="border-b py-2">
                    {{ $entry->display_name }}
                </p>
            @empty
                <p>No one is on the waitlist.</p>
            @endforelse
        </div>

        <a href="{{ route('tournaments.show', $tournament) }}"
           class="text-blue-600 underline">
            Back to Tournament
        </a>

    </div>
</x-app-layout>
