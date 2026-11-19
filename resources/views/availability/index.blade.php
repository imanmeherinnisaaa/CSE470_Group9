
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Submit Availability — {{ $tournament->name }}
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

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold mb-4">
                    Submit Your Available Time
                </h3>

                <form method="POST"
                      action="{{ route('availability.store', $tournament) }}"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Date
                        </label>

                        <input type="date"
                               name="available_date"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('available_date') }}"
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
                                   value="{{ old('start_time') }}"
                                   class="border rounded-md w-full p-2"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                End Time
                            </label>

                            <input type="time"
                                   name="end_time"
                                   value="{{ old('end_time') }}"
                                   class="border rounded-md w-full p-2"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            style="background:#2563eb;color:white;padding:12px 24px;border-radius:6px;font-weight:bold;">
                        Submit Availability
                    </button>
                </form>
            </div>

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
                            -
                            {{ $availability->end_time }}
                        </p>
                    </div>
                @empty
                    <p class="text-gray-600">
                        You have not submitted any availability yet.
                    </p>
                @endforelse
            </div>

            <a href="{{ route('tournaments.show', $tournament) }}"
               style="color:#2563eb;text-decoration:underline;">
                Back to Tournament
            </a>

        </div>
    </div>
</x-app-layout>
