<h3 class="font-semibold mb-2">{{ $title }}</h3>

@if ($slots->isEmpty())
    <p class="text-gray-500 text-sm">{{ $empty }}</p>
@else
    <table class="w-full text-sm">
        <thead><tr class="text-left"><th>Player</th><th>Rating</th><th></th></tr></thead>
        <tbody>
            @foreach ($slots as $slot)
                <tr class="border-t">
                    <td class="py-1">
                        {{ $slot->player->name }}
                        @if ($slot->player_id === $team->captain_id)<span class="px-2 py-0.5 text-xs bg-blue-600 text-white rounded">Captain</span>@endif
                        @if ($slot->locked)<span class="px-2 py-0.5 text-xs bg-gray-300 rounded">Locked</span>@endif
                    </td>
                    <td>{{ $slot->player->rating ?? 1000 }}</td>
                    <td class="text-right">
                        @if ($canEdit && $slot->player_id !== $team->captain_id)
                            <form method="POST" action="{{ route('teams.roster.remove', [$team, $slot]) }}" onsubmit="return confirm('Remove this player from the roster?');">
                                @csrf
                                @method('DELETE')
                                <button class="px-3 py-1 text-xs bg-red-600 text-white rounded">Remove</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
