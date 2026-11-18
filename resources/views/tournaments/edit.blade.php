<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit {{ $tournament->name }}</h2></x-slot>
    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('tournaments.update', $tournament) }}" class="bg-white p-6 shadow rounded space-y-4">
            @csrf
            @method('PUT')
            @include('tournaments._form')
            <button class="px-4 py-2 bg-gray-800 text-white rounded">Save changes</button>
        </form>
    </div>
</x-app-layout>