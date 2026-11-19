<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Create a team</h2></x-slot>

    <div class="py-6 max-w-xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if ($errors->any())
            <div class="p-3 bg-red-100 text-red-800 rounded">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div class="bg-white shadow rounded p-6">
            <p class="text-sm text-gray-600 mb-4">You become the team captain and its first starting player.</p>
            <form method="POST" action="{{ route('teams.store') }}">
                @csrf
                <label for="name" class="block text-sm mb-1">Team name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" maxlength="50" required class="w-full border-gray-300 rounded mb-4">
                <button class="px-4 py-2 bg-gray-800 text-white rounded">Create team</button>
            </form>
        </div>
    </div>
</x-app-layout>
