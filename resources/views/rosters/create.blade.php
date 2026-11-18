<x-app-layout>
<div class="container py-4" style="max-width: 540px;">
    <h1 class="h3 mb-3">Create a team</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <p class="text-muted">You become the team captain and its first starting player.</p>

    <form method="POST" action="{{ route('teams.store') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Team name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" maxlength="50" required>
        </div>
        <button class="btn btn-primary">Create team</button>
    </form>
</div>
</x-app-layout>