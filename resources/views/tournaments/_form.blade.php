@php $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm'; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium">Tournament name</label>
        <input name="name" class="{{ $input }}" value="{{ old('name', $tournament->name) }}">
        @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Game</label>
        <input name="game" class="{{ $input }}" value="{{ old('game', $tournament->game) }}">
        @error('game') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Format</label>
        <select name="format" id="format" class="{{ $input }}">
            @foreach (\App\Services\TournamentConfigurationService::FORMATS as $value => $label)
                <option value="{{ $value }}" @selected(old('format', $tournament->format) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('format') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Match type</label>
        <select name="best_of" class="{{ $input }}">
            @foreach ([1, 3, 5] as $n)
                <option value="{{ $n }}" @selected((int) old('best_of', $tournament->best_of) === $n)>Best of {{ $n }}</option>
            @endforeach
        </select>
        @error('best_of') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Minimum participants</label>
        <input type="number" name="min_participants" class="{{ $input }}" value="{{ old('min_participants', $tournament->min_participants) }}">
        @error('min_participants') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Maximum participants</label>
        <input type="number" name="max_participants" class="{{ $input }}" value="{{ old('max_participants', $tournament->max_participants) }}">
        @error('max_participants') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div id="swissBox" class="md:col-span-2">
        <label class="block text-sm font-medium">Swiss rounds</label>
        <input type="number" name="swiss_rounds" class="{{ $input }}" value="{{ old('swiss_rounds', $tournament->swiss_rounds) }}">
        @error('swiss_rounds') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @foreach ([
        'registration_opens_at' => 'Registration opens',
        'registration_closes_at' => 'Registration closes',
        'starts_at' => 'Schedule window starts',
        'ends_at' => 'Schedule window ends',
    ] as $field => $label)
        <div>
            <label class="block text-sm font-medium">{{ $label }}</label>
            <input type="datetime-local" name="{{ $field }}" class="{{ $input }}"
                   value="{{ old($field, optional($tournament->$field)->format('Y-m-d\TH:i')) }}">
            @error($field) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <div class="md:col-span-2">
        <label class="block text-sm font-medium">Game rules</label>
        <textarea name="rules" rows="5" class="{{ $input }}">{{ old('rules', $tournament->rules) }}</textarea>
        @error('rules') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<script>
    const format = document.getElementById('format');
    const swissBox = document.getElementById('swissBox');
    const toggle = () => swissBox.style.display = format.value === 'swiss' ? 'block' : 'none';
    format.addEventListener('change', toggle);
    toggle();
</script>