<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Player Availability — {{ $tournament->name }}
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

            {{-- Submit Availability --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">
                    Submit Your Availability
                </h3>

                <form method="POST"
                      action="{{ route('availability.store', $tournament) }}"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Available Date
                        </label>

                        <input type="date"
                               name="available_date"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('available_date') }}"
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
                                   value="{{ old('start_time') }}"
                                   class="border-gray-300 rounded-md shadow-sm w-full"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                End Time
                            </label>

                            <input type="time"
                                   name="end_time"
                                   value="{{ old('end_time') }}"
                                   class="border-gray-300 rounded-md shadow-sm w-full"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Submit Availability
                    </button>
                </form>
            </div>

            {{-- Previously Submitted Availability --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">
                    Your Submitted Availability
                </h3>

                @forelse ($availabilities as $availability)
                    <div class="border-b py-3">
                        <p class="font-medium">
                            {{ $availability->available_date }}
                        </p>

                        <p class="text-sm text-gray-600">
                            {{ $availability->start_time }}
                            to
                            {{ $availability->end_time }}
                        </p>
                    </div>
                @empty
                    <p class="text-gray-500">
                        You haven't submitted any availability yet.
                    </p>
                @endforelse
            </div>

            <a href="{{ route('tournaments.show', $tournament) }}"
               class="inline-block text-blue-600 hover:underline">
                Back to Tournament
            </a>

        </div>
    </div>
</x-app-layout>
