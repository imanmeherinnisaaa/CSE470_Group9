<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tournament Schedule — {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Create Station --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Create Station</h3>

                <form method="POST"
                      action="{{ route('schedule.stations.store', $tournament) }}"
                      class="flex flex-col sm:flex-row gap-3">
                    @csrf

                    <input type="text"
                           name="name"
                           placeholder="Enter station name"
                           class="border-gray-300 rounded-md shadow-sm flex-1"
                           required>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Add Station
                    </button>
                </form>
            </div>

            {{-- Create Time Slot --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Create Time Slot</h3>

                <form method="POST"
                      action="{{ route('schedule.time-slots.store', $tournament) }}"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Select Station
                        </label>

                        <select name="station_id"
                                class="border-gray-300 rounded-md shadow-sm w-full"
                                required>
                            <option value="">Choose a station</option>

                            @foreach ($stations as $station)
                                <option value="{{ $station->id }}">
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date
                        </label>

                        <input type="date"
                               name="slot_date"
                               min="{{ now()->toDateString() }}"
                               class="border-gray-300 rounded-md shadow-sm w-full"
                               required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Start Time
                            </label>

                            <input type="time"
                                   name="start_time"
                                   class="border-gray-300 rounded-md shadow-sm w-full"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                End Time
                            </label>

                            <input type="time"
                                   name="end_time"
                                   class="border-gray-300 rounded-md shadow-sm w-full"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Add Time Slot
                    </button>
                </form>
            </div>

            {{-- Matches --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Matches</h3>

                @forelse ($matches as $match)
                    <div class="border rounded-lg p-4 mb-4">

                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">

                            <div>
                                <p class="font-semibold">
                                    Round {{ $match->round?->number ?? '—' }}
                                    — Match {{ $match->match_number }}
                                </p>

                                <p class="text-gray-700 mt-1">
                                    {{ $match->entry1?->display_name ?? 'TBD' }}
                                    vs
                                    {{ $match->entry2?->display_name ?? 'TBD' }}
                                </p>

                                @if ($match->timeSlot)
                                    <p class="text-sm text-green-700 mt-2">
                                        Scheduled:
                                        {{ $match->timeSlot->slot_date }}
                                        ({{ $match->timeSlot->start_time }} -
                                        {{ $match->timeSlot->end_time }})
                                        at {{ $match->timeSlot->station?->name ?? 'Station unavailable' }}
                                    </p>
                                @else
                                    <p class="text-sm text-orange-600 mt-2">
                                        Not scheduled yet
                                    </p>
                                @endif
                            </div>

                            <form method="POST"
                                  action="{{ route('schedule.assign', $tournament) }}"
                                  class="flex flex-col sm:flex-row gap-2">
                                @csrf

                                <input type="hidden"
                                       name="match_id"
                                       value="{{ $match->id }}">

                                <select name="time_slot_id"
                                        class="border-gray-300 rounded-md shadow-sm"
                                        required>
                                    <option value="">Choose a time slot</option>

                                    @foreach ($timeSlots as $timeSlot)
                                        <option value="{{ $timeSlot->id }}"
                                            @selected($match->time_slot_id == $timeSlot->id)>
                                            {{ $timeSlot->slot_date }}
                                            | {{ $timeSlot->start_time }} - {{ $timeSlot->end_time }}
                                            | {{ $timeSlot->station?->name ?? 'No station' }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                    Assign
                                </button>
                            </form>

                        </div>
                    </div>

                @empty
                    <p class="text-gray-500">
                        No matches have been generated for this tournament yet.
                    </p>
                @endforelse
            </div>

            {{-- Available Time Slots --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">
                    Available Time Slots
                </h3>

                @forelse ($timeSlots as $timeSlot)
                    <div class="border-b py-3">
                        <p class="font-medium">
                            {{ $timeSlot->station?->name ?? 'No station assigned' }}
                        </p>

                        <p class="text-sm text-gray-600">
                            {{ $timeSlot->slot_date }}
                            | {{ $timeSlot->start_time }} - {{ $timeSlot->end_time }}
                        </p>
                    </div>
                @empty
                    <p class="text-gray-500">
                        No time slots have been created for this tournament yet.
                    </p>
                @endforelse
            </div>

            {{-- Back Button --}}
            <a href="{{ route('tournaments.show', $tournament) }}"
               class="inline-block text-blue-600 hover:underline">
                Back to Tournament
            </a>

        </div>
    </div>
</x-app-layout>
