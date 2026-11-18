<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $tournament->name }}</h2></x-slot>
    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="p-3 bg-green-100 rounded">{{ session('status') }}</div>
        @endif
        @if ($errors->has('bracket'))
            <div class="p-3 bg-red-100 rounded">{{ $errors->first('bracket') }}</div>
        @endif

        <div class="bg-white shadow rounded p-6 grid grid-cols-2 gap-3 text-sm">
            <div><b>Game:</b> {{ $tournament->game }}</div>
            <div><b>Format:</b> {{ $tournament->formatLabel() }}</div>
            <div><b>Participants:</b> {{ $tournament->min_participants }} – {{ $tournament->max_participants }}</div>
            <div><b>Match type:</b> Best of {{ $tournament->best_of }}</div>
            @if ($tournament->format === 'swiss')
                <div><b>Swiss rounds:</b> {{ $tournament->swiss_rounds }}</div>
            @endif
            <div><b>Status:</b> {{ $tournament->status }}</div>
            <div><b>Registration:</b> {{ $tournament->registration_opens_at?->format('d M Y H:i') ?? '—' }} → {{ $tournament->registration_closes_at?->format('d M Y H:i') ?? '—' }}</div>
            <div><b>Schedule window:</b> {{ $tournament->starts_at?->format('d M Y H:i') ?? '—' }} → {{ $tournament->ends_at?->format('d M Y H:i') ?? '—' }}</div>
            <div class="col-span-2"><b>Estimated matches at full capacity:</b> {{ $estimate }}</div>
            <div class="col-span-2"><b>Rules:</b><p class="whitespace-pre-line">{{ $tournament->rules ?: '—' }}</p></div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('tournaments.index') }}" class="px-4 py-2 bg-gray-200 rounded">Back</a>

            @if ($tournament->isElimination())
                <a href="{{ route('bracket.show', $tournament) }}" class="px-4 py-2 bg-gray-200 rounded">View bracket</a>
            @endif

            @if (auth()->id() === $tournament->organizer_id)
                @if ($tournament->isElimination())
                    <form method="POST" action="{{ route('bracket.generate', $tournament) }}">
                        @csrf
                        <button class="px-4 py-2 bg-gray-800 text-white rounded">
                            {{ $tournament->isLocked() ? 'Regenerate bracket' : 'Generate bracket' }}
                        </button>
                    </form>
                @endif

                @unless ($tournament->isLocked())
                    <a href="{{ route('tournaments.edit', $tournament) }}" class="px-4 py-2 bg-gray-200 rounded">Edit</a>
                @endunless

                <form method="POST" action="{{ route('tournaments.destroy', $tournament) }}" onsubmit="return confirm('Delete this tournament?')">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 bg-red-600 text-white rounded">Delete</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>