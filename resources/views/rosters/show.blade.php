
<x-app-layout>
@php($canEdit = $isCaptain && ! $locked)
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $team->name }}</h1>
            <p class="text-muted mb-0">
                Captain: {{ $team->captain->name }}.
                Starters {{ $starters->count() }} of {{ $limits['max_starters'] }},
                substitutes {{ $substitutes->count() }} of {{ $limits['max_substitutes'] }}.
                @if ($limits['tournament_bound'])
                    The team's tournament entry needs {{ $limits['min_starters'] === $limits['max_starters'] ? 'exactly ' . $limits['min_starters'] : $limits['min_starters'] . ' to ' . $limits['max_starters'] }} starters.
                @endif
            </p>
        </div>
        @if ($locked)
            <span class="badge bg-secondary">Roster locked</span>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @if ($locked)
        <div class="alert alert-info">This roster is locked. Players cannot be added, removed or substituted until the team's tournament is over.</div>
    @endif

    <div class="row">
        <div class="col-md-6 mb-4">
            @include('rosters._slots', ['title' => 'Starters', 'slots' => $starters, 'empty' => 'No starters yet.', 'team' => $team, 'canEdit' => $canEdit])
        </div>
        <div class="col-md-6 mb-4">
            @include('rosters._slots', ['title' => 'Substitutes', 'slots' => $substitutes, 'empty' => 'No substitutes yet.', 'team' => $team, 'canEdit' => $canEdit])
        </div>
    </div>

    @if ($canEdit)
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-body">
                    <h2 class="h5">Add a player</h2>
                    <form method="POST" action="{{ route('teams.roster.add', $team) }}">
                        @csrf
                        <div class="mb-2">
                            <label for="email" class="form-label">Player email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="slot_type" class="form-label">Role on the roster</label>
                            <select name="slot_type" id="slot_type" class="form-select">
                                <option value="starter" @selected(old('slot_type') === 'starter')>Starter</option>
                                <option value="substitute" @selected(old('slot_type') === 'substitute')>Substitute</option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm">Add player</button>
                    </form>
                </div></div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-body">
                    <h2 class="h5">Make a substitution</h2>
                    @if ($starters->isEmpty() || $substitutes->isEmpty())
                        <p class="text-muted mb-0">You need at least one starter and one substitute.</p>
                    @else
                        <form method="POST" action="{{ route('teams.roster.substitute', $team) }}">
                            @csrf
                            <div class="mb-2">
                                <label for="starter_slot_id" class="form-label">Starter coming off</label>
                                <select name="starter_slot_id" id="starter_slot_id" class="form-select" required>
                                    @foreach ($starters as $slot)
                                        <option value="{{ $slot->id }}">{{ $slot->player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="substitute_slot_id" class="form-label">Substitute coming on</label>
                                <select name="substitute_slot_id" id="substitute_slot_id" class="form-select" required>
                                    @foreach ($substitutes as $slot)
                                        <option value="{{ $slot->id }}">{{ $slot->player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary btn-sm">Swap players</button>
                        </form>
                    @endif
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <h2 class="h5">Rename team</h2>
                <form method="POST" action="{{ route('teams.update', $team) }}" class="d-flex gap-2">
                    @csrf @method('PATCH')
                    <input type="text" name="name" class="form-control" value="{{ old('name', $team->name) }}" maxlength="50" required>
                    <button class="btn btn-outline-primary">Rename</button>
                </form>
            </div>
            <div class="col-md-6 mb-4">
                <h2 class="h5">Delete team</h2>
                <form method="POST" action="{{ route('teams.destroy', $team) }}"
                      onsubmit="return confirm('Delete this team and its roster?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger">Delete team</button>
                </form>
            </div>
        </div>
    @endif
</div>
</x-app-layout>
