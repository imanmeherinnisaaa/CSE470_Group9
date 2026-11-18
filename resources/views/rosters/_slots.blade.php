<h2 class="h5">{{ $title }}</h2>

@if ($slots->isEmpty())
    <p class="text-muted">{{ $empty }}</p>
@else
    <table class="table table-sm align-middle">
        <thead><tr><th>Player</th><th>Rating</th><th></th></tr></thead>
        <tbody>
            @foreach ($slots as $slot)
                <tr>
                    <td>
                        {{ $slot->player->name }}
                        @if ($slot->player_id === $team->captain_id)<span class="badge bg-primary">Captain</span>@endif
                        @if ($slot->locked)<span class="badge bg-secondary">Locked</span>@endif
                    </td>
                    <td>{{ $slot->player->rating ?? 1000 }}</td>
                    <td class="text-end">
                        @if ($canEdit && $slot->player_id !== $team->captain_id)
                            <form method="POST" action="{{ route('teams.roster.remove', [$team, $slot]) }}"
                                  onsubmit="return confirm('Remove this player from the roster?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Remove</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
