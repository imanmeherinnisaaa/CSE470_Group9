
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-900 rounded">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-3 bg-red-100 text-red-900 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white shadow rounded p-6 grid grid-cols-2 gap-3 text-sm text-gray-900">
            <div><b>Game:</b> {{ $tournament->game }}</div>
            <div><b>Format:</b> {{ $tournament->formatLabel() }}</div>

            <div>
                <b>Participants:</b>
                {{ $tournament->confirmedCount() }} / {{ $tournament->max_participants ?? 'Unlimited' }}
            </div>

            <div><b>Match type:</b> Best of {{ $tournament->best_of }}</div>

            @if ($tournament->format === 'swiss')
                <div><b>Swiss rounds:</b> {{ $tournament->swiss_rounds }}</div>
            @endif

            <div><b>Status:</b> {{ $tournament->status }}</div>

            <div class="col-span-2">
                <b>Registration:</b>
                {{ $tournament->registration_opens_at?->format('d M Y H:i') ?? '—' }}
                →
                {{ $tournament->registration_closes_at?->format('d M Y H:i') ?? '—' }}
            </div>

            <div class="col-span-2">
                <b>Schedule window:</b>
                {{ $tournament->starts_at?->format('d M Y H:i') ?? '—' }}
                →
                {{ $tournament->ends_at?->format('d M Y H:i') ?? '—' }}
            </div>

            <div class="col-span-2">
                <b>Estimated matches at full capacity:</b>
                {{ $estimate }}
            </div>

            <div class="col-span-2">
                <b>Rules:</b>
                <p class="whitespace-pre-line">
                    {{ $tournament->rules ?: '—' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 items-center">

            <a href="{{ route('tournaments.index') }}"
               class="bg-gray-200 text-gray-900 px-4 py-2 font-semibold rounded">
                Back
            </a>

            @if ($tournament->isElimination())
                <a href="{{ route('bracket.show', $tournament) }}"
                   class="bg-gray-700 text-white px-4 py-2 font-semibold rounded">
                    View Bracket
                </a>
            @endif

            {{-- Direct player registration --}}
            @auth
                @if (
                    auth()->id() !== $tournament->organizer_id &&
                    in_array(auth()->user()->role, ['player', 'team_captain'], true)
                )
                    <form method="POST"
                          action="{{ route('tournaments.entry.store', $tournament) }}">
                        @csrf

                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 font-semibold rounded">
                            Enter Tournament
                        </button>
                    </form>

                    <a href="{{ route('tournaments.entry.show', $tournament) }}"
                       class="bg-gray-600 text-white px-4 py-2 font-semibold rounded">
                        View Participants
                    </a>

                    <a href="{{ route('availability.index', $tournament) }}"
                       class="bg-green-700 text-white px-4 py-2 font-semibold rounded">
                        Submit Availability
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}"
                   class="bg-blue-600 text-white px-4 py-2 font-semibold rounded">
                    Log in to Enter
                </a>
            @endauth

            {{-- Organizer controls --}}
            @auth
                @if (auth()->id() === $tournament->organizer_id)

                    <a href="{{ route('schedule.index', $tournament) }}"
                       class="bg-blue-700 text-white px-4 py-2 font-semibold rounded">
                        Manage Schedule
                    </a>

                    @if ($tournament->isElimination())
                        <form method="POST"
                              action="{{ route('bracket.generate', $tournament) }}">
                            @csrf

                            <button type="submit"
                                    class="bg-gray-800 text-white px-4 py-2 font-semibold rounded">
                                {{ $tournament->isLocked() ? 'Regenerate bracket' : 'Generate bracket' }}
                            </button>
                        </form>
                    @endif

                    @unless ($tournament->isLocked())
                        <a href="{{ route('tournaments.edit', $tournament) }}"
                           class="bg-gray-200 text-gray-900 px-4 py-2 font-semibold rounded">
                            Edit
                        </a>
                    @endunless

                    <form method="POST"
                          action="{{ route('tournaments.destroy', $tournament) }}"
                          onsubmit="return confirm('Delete this tournament?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="bg-red-600 text-white px-4 py-2 font-semibold rounded">
                            Delete
                        </button>
                    </form>

                @endif
            @endauth

        </div>
    </div>
</x-app-layout>
