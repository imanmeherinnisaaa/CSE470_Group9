<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">{{ $team->name }}</h2>
            @if ($locked)<span class="px-2 py-1 text-xs bg-gray-300 rounded">Roster locked</span>@endif
        </div>
    </x-slot>

    @php($canEdit = $isCaptain && ! $locked)
    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status')) <div class="p-3 bg-green-100 rounded">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="p-3 bg-red-100 text-red-800 rounded">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        @if ($locked)
            <div class="p-3 bg-blue-100 rounded">This roster is locked. Players cannot be added, removed or substituted until the team's tournament is over.</div>
        @endif

        <p class="text-sm text-gray-600">
            Captain: {{ $team->captain->name }}.
            Starters {{ $starters->count() }} of {{ $limits['max_starters'] }},
            substitutes {{ $substitutes->count() }} of {{ $limits['max_substitutes'] }}.
            @if ($limits['tournament_bound'])
                The team's tournament entry needs {{ $limits['min_starters'] === $limits['max_starters'] ? 'exactly ' . $limits['min_starters'] : $limits['min_starters'] . ' to ' . $limits['max_starters'] }} starters.
            @endif
        </p>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white shadow rounded p-6">
                @include('rosters._slots', ['title' => 'Starters', 'slots' => $starters, 'empty' => 'No starters yet.', 'team' => $team, 'canEdit' => $canEdit])
            </div>
            <div class="bg-white shadow rounded p-6">
                @include('rosters._slots', ['title' => 'Substitutes', 'slots' => $substitutes, 'empty' => 'No substitutes yet.', 'team' => $team, 'canEdit' => $canEdit])
            </div>
        </div>

        @if ($canEdit)
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white shadow rounded p-6">
                    <h3 class="font-semibold mb-2">Add a player</h3>
                    <form method="POST" action="{{ route('teams.roster.add', $team) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="email" class="block text-sm mb-1">Player email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full border-gray-300 rounded">
                        </div>
                        <div>
                            <label for="slot_type" class="block text-sm mb-1">Role on the roster</label>
                            <select name="slot_type" id="slot_type" class="w-full border-gray-300 rounded">
                                <option value="starter" @selected(old('slot_type') === 'starter')>Starter</option>
                                <option value="substitute" @selected(old('slot_type') === 'substitute')>Substitute</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 bg-gray-800 text-white rounded">Add player</button>
                    </form>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="font-semibold mb-2">Make a substitution</h3>
                    @if ($starters->isEmpty() || $substitutes->isEmpty())
                        <p class="text-sm text-gray-500">You need at least one starter and one substitute.</p>
                    @else
                        <form method="POST" action="{{ route('teams.roster.substitute', $team) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="starter_slot_id" class="block text-sm mb-1">Starter coming off</label>
                                <select name="starter_slot_id" id="starter_slot_id" class="w-full border-gray-300 rounded" required>
                                    @foreach ($starters as $slot)<option value="{{ $slot->id }}">{{ $slot->player->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label for="substitute_slot_id" class="block text-sm mb-1">Substitute coming on</label>
                                <select name="substitute_slot_id" id="substitute_slot_id" class="w-full border-gray-300 rounded" required>
                                    @foreach ($substitutes as $slot)<option value="{{ $slot->id }}">{{ $slot->player->name }}</option>@endforeach
                                </select>
                            </div>
                            <button class="px-4 py-2 bg-gray-800 text-white rounded">Swap players</button>
                        </form>
                    @endif
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="font-semibold mb-2">Rename team</h3>
                    <form method="POST" action="{{ route('teams.update', $team) }}" class="flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name" value="{{ old('name', $team->name) }}" maxlength="50" required class="flex-1 border-gray-300 rounded">
                        <button class="px-4 py-2 bg-gray-200 rounded">Rename</button>
                    </form>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="font-semibold mb-2">Delete team</h3>
                    <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team and its roster?');">
                        @csrf
                        @method('DELETE')
                        <button class="px-4 py-2 bg-red-600 text-white rounded">Delete team</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
