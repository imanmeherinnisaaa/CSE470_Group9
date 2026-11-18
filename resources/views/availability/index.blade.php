
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tournament Schedule — {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:6px;">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;">
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
                            style="background:#2563eb;color:white;padding:10px 20px;border-radius:6px;font-weight:bold;">
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
                        <label class="block text-sm font-medium mb-1">
                            Select Station
                        </label>

                        <select name="station_id"
                                class="border rounded-md w-full p-2"
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
                        <label class="block text-sm font-medium mb-1">
                            Date
                        </label>

                        <input type="date"
                               name="slot_date"
                               min="{{ now()->toDateString() }}"
                               class="border rounded-md w-full p-2"
                               required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Start Time
                            </label>

                            <input type="time"
                                   name="start_time"
                                   class="border rounded-md w-full p-2"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                End Time
                            </label>

                            <input type="time"
                                   name="end_time"
                                   class="border rounded-md w-full p-2"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            style="background-color:#2563eb !important;color:#ffffff !important;padding:12px 24px;border-radius:6px;font-weight:bold;display:inline-block;visibility:visible;opacity:1;cursor:pointer;">
                        Add Time Slot
                    </button>
                </form>
            </div>

            {{-- Matches --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Matches</h3>

                @forelse ($matches as $match)
                    <div class="border rounded-lg p-4 mb-4">

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

                        <form method="POST"
                              action="{{ route('schedule.assign', $tournament) }}"
                              class="flex flex-col sm:flex-row gap-2 mt-3">
                            @csrf

                            <input type="hidden"
                                   name="match_id"
                                   value="{{ $match->id }}">

                            <select name="time_slot_id"
                                    class="border rounded-md p-2"
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
                                    style="background:#2563eb;color:white;padding:10px 20px;border-radius:6px;font-weight:bold;">
                                Assign
                            </button>
                        </form>
                    </div>
                @empty
                    <p>No matches have been generated for this tournament yet.</p>
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
                    <p>No time slots have been created for this tournament yet.</p>
                @endforelse
            </div>

            <a href="{{ route('tournaments.show', $tournament) }}"
               style="color:#2563eb;text-decoration:underline;">
                Back to Tournament
            </a>

        </div>
    </div>
</x-app-layout>
