
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Tournament Details - {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">

        {{-- Success message --}}
        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded">
                {{ session('status') }}
            </div>
        @endif

        {{-- Error messages --}}
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Tournament information --}}
        <div class="bg-white rounded shadow p-6 space-y-3">

            <h3 class="text-2xl font-bold text-gray-800">
                {{ $tournament->name }}
            </h3>

            <p>
                <strong>Game:</strong>
                {{ $tournament->game }}
            </p>

            <p>
                <strong>Format:</strong>
                {{ $tournament->formatLabel() }}
            </p>

            <p>
                <strong>Status:</strong>
                {{ ucfirst($tournament->status) }}
            </p>

            <p>
                <strong>Participants:</strong>
                {{ $confirmed->count() }} / {{ $tournament->max_participants }}
            </p>

            <p>
                <strong>Minimum Participants:</strong>
                {{ $tournament->min_participants }}
            </p>

            <p>
                <strong>Maximum Participants:</strong>
                {{ $tournament->max_participants }}
            </p>

            <p>
                <strong>Registration Opens:</strong>
                {{ $tournament->registration_opens_at
                    ? $tournament->registration_opens_at->format('d M Y, h:i A')
                    : 'Not specified' }}
            </p>

            <p>
                <strong>Registration Closes:</strong>
                {{ $tournament->registration_closes_at
                    ? $tournament->registration_closes_at->format('d M Y, h:i A')
                    : 'Not specified' }}
            </p>

            <p>
                <strong>Start Date:</strong>
                {{ $tournament->starts_at
                    ? $tournament->starts_at->format('d M Y, h:i A')
                    : 'Not specified' }}
            </p>

            <p>
                <strong>End Date:</strong>
                {{ $tournament->ends_at
                    ? $tournament->ends_at->format('d M Y, h:i A')
                    : 'Not specified' }}
            </p>

            <p>
                <strong>Estimated Matches:</strong>
                {{ $estimate }}
            </p>

            @if ($tournament->rules)
                <div class="pt-3 border-t">
                    <h4 class="font-bold mb-2">Tournament Rules</h4>
                    <p class="whitespace-pre-line">
                        {{ $tournament->rules }}
                    </p>
                </div>
            @endif

        </div>

        {{-- Navigation and action buttons --}}
        <div class="flex flex-wrap gap-3">

            <a href="{{ route('tournaments.index') }}"
               style="display:inline-block;background:#374151;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                Back to Tournaments
            </a>

            @if (in_array($tournament->format, ['single_elimination', 'double_elimination'], true))
                <a href="{{ route('bracket.show', $tournament) }}"
                   style="display:inline-block;background:#7c3aed;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                    View Bracket
                </a>
            @endif

            {{-- Player actions --}}
            @if (auth()->check()
                && in_array(auth()->user()->role, ['player', 'team_captain'], true)
                && auth()->id() !== $tournament->organizer_id)

                <a href="{{ route('tournaments.entry.show', $tournament) }}"
                   style="display:inline-block;background:#2563eb;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                    Enter Tournament
                </a>

                @if ($confirmed->contains(function ($entry) {
                    return $entry->user_id === auth()->id();
                }))
                    <a href="{{ route('availability.index', $tournament) }}"
                       style="display:inline-block;background:#15803d;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                        Submit Availability
                    </a>
                @endif

            @endif

            {{-- Organizer actions --}}
            @if (auth()->check() && auth()->id() === $tournament->organizer_id)

                <a href="{{ route('schedule.index', $tournament) }}"
                   style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                    Manage Schedule
                </a>

                <a href="{{ route('tournaments.edit', $tournament) }}"
                   style="display:inline-block;background:#d97706;color:#ffffff;padding:10px 18px;border-radius:6px;font-weight:bold;text-decoration:none;">
                    Edit Tournament
                </a>

                @if (in_array($tournament->format, ['single_elimination', 'double_elimination'], true))

                    <form method="POST"
                          action="{{ route('bracket.generate', $tournament) }}">
                        @csrf

                        <button type="submit"
                                style="display:inline-block;background:#6d28d9;color:#ffffff;padding:10px 18px;border:0;border-radius:6px;font-weight:bold;cursor:pointer;">
                            Generate / Regenerate Bracket
                        </button>
                    </form>

                @endif

                <form method="POST"
                      action="{{ route('tournaments.destroy', $tournament) }}"
                      onsubmit="return confirm('Are you sure you want to delete this tournament?')">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            style="display:inline-block;background:#dc2626;color:#ffffff;padding:10px 18px;border:0;border-radius:6px;font-weight:bold;cursor:pointer;">
                        Delete Tournament
                    </button>
                </form>

            @endif

        </div>

        {{-- Confirmed participants --}}
        <div class="bg-white rounded shadow p-6">

            <h3 class="text-xl font-bold mb-4">
                Confirmed Participants
            </h3>

            @forelse ($confirmed as $entry)
                <p class="border-b py-2">
                    {{ $entry->display_name }}
                </p>
            @empty
                <p>No confirmed participants yet.</p>
            @endforelse

        </div>

        {{-- Waitlist --}}
        <div class="bg-white rounded shadow p-6">

            <h3 class="text-xl font-bold mb-4">
                Waitlist
            </h3>

            @forelse ($waitlist as $entry)
                <p class="border-b py-2">
                    {{ $entry->display_name }}
                </p>
            @empty
                <p>No one is on the waitlist.</p>
            @endforelse

        </div>

    </div>
</x-app-layout>
