
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl">{{ $tournament->name }}: Bracket</h2>
            <a href="{{ route('tournaments.show', $tournament) }}" class="underline text-sm">Back</a>
        </div>
    </x-slot>

    <div class="py-6 px-4 space-y-8">

        @if (session('status'))
            <div class="p-3 bg-green-100 rounded">
                {{ session('status') }}
            </div>
        @endif

        @if ($rounds->isEmpty())
            <p class="text-gray-500">No bracket has been generated yet.</p>
        @endif

        @foreach (['winners' => 'Winners Bracket', 'losers' => 'Losers Bracket', 'grand_final' => 'Grand Final'] as $key => $title)

            @continue(!isset($rounds[$key]))

            <section>
                <h3 class="font-semibold text-lg mb-2">
                    {{ $key === 'winners' && $tournament->format === 'single_elimination' ? 'Bracket' : $title }}
                </h3>

                <div class="flex gap-6 overflow-x-auto pb-4">

                    @foreach ($rounds[$key] as $round)

                        <div class="min-w-[220px] flex flex-col">

                            <h4 class="text-sm font-semibold text-gray-600 mb-2">
                                {{ $round->name }}
                            </h4>

                            <div class="flex flex-col justify-around flex-1 gap-3">

                                @foreach ($round->matches->where('status', '!=', 'void') as $match)

                                    <div class="border rounded bg-white shadow-sm text-sm {{ $match->is_bye ? 'opacity-60' : '' }}">

                                        <div class="px-2 py-1 text-xs text-gray-500">
                                            Match #{{ $match->match_number }} · {{ $match->status }}
                                        </div>

                                        @foreach ([1, 2] as $slot)

                                            <div class="px-2 py-1 border-t {{ $match->winner_entry_id && $match->winner_entry_id === $match->{'entry'.$slot.'_id'} ? 'font-bold bg-green-50' : '' }}">
                                                {{ $match->slotLabel($slot) }}
                                            </div>

                                        @endforeach

                                        @if (auth()->check() && $match->status === 'ready')

                                            @php
                                                $isParticipant =
                                                    (int) ($match->entry1?->user_id ?? 0) === (int) auth()->id() ||
                                                    (int) ($match->entry2?->user_id ?? 0) === (int) auth()->id() ||
                                                    (int) ($match->entry1?->team?->captain_id ?? 0) === (int) auth()->id() ||
                                                    (int) ($match->entry2?->team?->captain_id ?? 0) === (int) auth()->id();
                                            @endphp

                                            @if ($isParticipant)
                                                <div class="p-2 border-t">
                                                    <a href="{{ route('matches.result.create', $match) }}"
                                                       class="text-blue-600 underline font-semibold">
                                                        Report Result
                                                    </a>
                                                </div>
                                            @endif

                                        @endif

                                    </div>

                                @endforeach

                            </div>
                        </div>

                    @endforeach

                </div>
            </section>

        @endforeach

    </div>
</x-app-layout>
