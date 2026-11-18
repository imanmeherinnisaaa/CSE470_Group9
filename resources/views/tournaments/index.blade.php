<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Tournaments</h2>
            @if (auth()->user()->role === 'organizer')
                <a href="{{ route('tournaments.create') }}" class="px-3 py-1 bg-gray-800 text-white rounded">New tournament</a>
            @endif
        </div>
    </x-slot>
    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8">
        @if (session('status')) <div class="mb-4 p-3 bg-green-100 rounded">{{ session('status') }}</div> @endif
        <div class="bg-white shadow rounded divide-y">
            @forelse ($tournaments as $t)
                <a href="{{ route('tournaments.show', $t) }}" class="block p-4 hover:bg-gray-50">
                    <div class="font-semibold">{{ $t->name }}</div>
                    <div class="text-sm text-gray-500">{{ $t->game }} · {{ $t->formatLabel() }} · {{ $t->status }}</div>
                </a>
            @empty
                <p class="p-4 text-gray-500">No tournaments yet.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $tournaments->links() }}</div>
    </div>
</x-app-layout>