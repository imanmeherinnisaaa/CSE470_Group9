
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Player Availability - {{ $tournament->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success Message --}}
            @if (session('success'))
                <div style="background-color: #dcfce7; color: #166534;"
                     class="border px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div style="background-color: #fee2e2; color: #991b1b;"
                     class="border px-4 py-3 rounded">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Availability Form --}}
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <h3 class="text-lg font-semibold mb-4 text-gray-900">
                    Submit Your Availability
                </h3>

                <form method="POST"
                      action="{{ route('availability.store', $tournament) }}"
                      class="space-y-5">

                    @csrf

                    {{-- Date --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-2">
                            Available Date
                        </label>

                        <input type="date"
                               name="available_date"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('available_date') }}"
                               required
                               class="border border-gray-300 rounded-md w-full p-2 text-gray-900">
                    </div>

                    {{-- Start and End Time --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-2">
                                Start Time
                            </label>

                            <input type="time"
                                   name="start_time"
                                   value="{{ old('start_time') }}"
                                   required
                                   class="border border-gray-300 rounded-md w-full p-2 text-gray-900">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-2">
                                End Time
                            </label>

                            <input type="time"
                                   name="end_time"
                                   value="{{ old('end_time') }}"
                                   required
                                   class="border border-gray-300 rounded-md w-full p-2 text-gray-900">
                        </div>

                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            style="background-color: #15803d; color: white;"
                            class="px-6 py-3 font-semibold rounded-md hover:opacity-90">
                        Submit Availability
                    </button>

                </form>
            </div>

            {{-- Previously Submitted Availability --}}
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <h3 class="text-lg font-semibold mb-4 text-gray-900">
                    Your Submitted Availability
                </h3>

                @forelse ($availabilities as $availability)

                    <div class="border-b py-3">
                        <p class="font-medium text-gray-900">
                            {{ $availability->available_date }}
                        </p>

                        <p class="text-sm text-gray-600">
                            {{ $availability->start_time }}
                            to
                            {{ $availability->end_time }}
                        </p>
                    </div>

                @empty

                    <p class="text-gray-600">
                        You haven't submitted any availability yet.
                    </p>

                @endforelse

            </div>

            {{-- Back Button --}}
            <a href="{{ route('tournaments.show', $tournament) }}"
               style="background-color: #374151; color: white;"
               class="inline-block px-4 py-2 rounded-md font-semibold">
                Back to Tournament
            </a>

        </div>
    </div>
</x-app-layout>
