
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Report Match Result</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto p-6">

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-lg font-bold mb-4">
                Match #{{ $gameMatch->id }}
            </h3>

            <p class="mb-2">
                **Player 1:** {{ $gameMatch->entry1?->display_name ?? 'TBD' }}
            </p>

            <p class="mb-4">
                **Player 2:** {{ $gameMatch->entry2?->display_name ?? 'TBD' }}
            </p>

            <p class="mb-4">
                **Status:** {{ ucfirst($gameMatch->status) }}
            </p>

            <h4 class="font-semibold mb-2">Submitted Reports</h4>

            @forelse($gameMatch->reports as $report)
                <div class="border p-3 rounded mb-2">
                    {{ $report->reporter->name }}:
                    {{ $report->score1 }} - {{ $report->score2 }}
                </div>
            @empty
                <p class="text-gray-500 mb-4">No reports submitted yet.</p>
            @endforelse

            @php
                $canReport =
                    $gameMatch->entry1?->user_id === auth()->id() ||
                    $gameMatch->entry2?->user_id === auth()->id() ||
                    $gameMatch->entry1?->team?->captain_id === auth()->id() ||
                    $gameMatch->entry2?->team?->captain_id === auth()->id();

                $alreadyReported = $gameMatch->reports
                    ->contains('reporter_id', auth()->id());
            @endphp

            @if($gameMatch->status === 'ready' && $canReport && !$alreadyReported)
                <form method="POST"
                    action="{{ route('matches.result.store', $gameMatch) }}"
                    class="mt-6">

                    @csrf

                    <label class="block mb-2">Player 1 Score</label>
                    <input type="number" name="score1" min="0" required
                        class="border rounded p-2 w-full mb-4">

                    <label class="block mb-2">Player 2 Score</label>
                    <input type="number" name="score2" min="0" required
                        class="border rounded p-2 w-full mb-4">

                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded">
                        Submit Result
                    </button>
                </form>
            @elseif($alreadyReported && $gameMatch->status === 'ready')
                <p class="text-blue-700 mt-4">
                    You have already submitted your report. Waiting for confirmation.
                </p>
            @elseif($gameMatch->status === 'conflicting')
                <p class="text-red-700 mt-4">
                    Reports disagree. This match requires review.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
