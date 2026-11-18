<!DOCTYPE html>
<html>
<head>
    <title>F5 - Swiss Pairing Engine</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f5f7; margin: 35px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 25px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0 25px; }
        th, td { padding: 11px; border: 1px solid #ddd; text-align: left; }
        th { background: #edf2f7; }
        button { background: #2563eb; color: white; padding: 10px 16px; border: 0; border-radius: 5px; cursor: pointer; }
        select { padding: 9px; margin: 8px 0; }
        .message { background: #dcfce7; padding: 12px; margin: 12px 0; }
        .error { color: #b91c1c; }
        .bye { color: #166534; font-weight: bold; }
        a { margin-right: 15px; }
        .round { margin-top: 30px; }
    </style>
</head>
<body>
<div class="container">

    <h1>F5 - Swiss Pairing Engine</h1>
    <p>Generate score-based rounds, avoid rematches, and track tournament standings.</p>

    <p>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('seeding.demo') }}">F3 Seeding Engine</a>
    </p>

    @if(session('status'))
        <div class="message">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <h2>Select Swiss Tournament</h2>

    <form method="GET" action="{{ route('swiss.demo') }}">
        <select name="tournament_id" required>
            <option value="">Choose a tournament</option>
            @foreach($tournaments as $item)
                <option value="{{ $item->id }}"
                    {{ $tournament?->id == $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
        <button type="submit">Load Tournament</button>
    </form>

    @if($tournaments->isEmpty())
        <p>No Swiss-format tournaments were found under your account.</p>
    @endif

    @if($tournament)
        <h2>{{ $tournament->name }}</h2>

        <p>
            Confirmed entries: {{ $entries->count() }}
            |
            Configured rounds: {{ $tournament->swiss_rounds ?? 'Not specified' }}
        </p>

        <h2>Current Standings</h2>

        @if(empty($standings))
            <p>No confirmed entries are available.</p>
        @else
            <table>
                <tr>
                    <th>Rank</th>
                    <th>Seed</th>
                    <th>Participant / Team</th>
                    <th>Points</th>
                    <th>Bye Received</th>
                </tr>
                @foreach($standings as $index => $participant)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $participant['seed'] ?? '-' }}</td>
                        <td>{{ $participant['name'] }}</td>
                        <td>{{ $participant['score'] }}</td>
                        <td>{{ $participant['has_received_bye'] ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <h2>Generate Next Round</h2>

        <p>Next round: {{ $nextRound }}</p>

        <form method="POST" action="{{ route('swiss.demo.generate') }}">
            @csrf
            <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
            <button type="submit">Generate and Save Round {{ $nextRound }}</button>
        </form>

        @if($rounds->isNotEmpty())
            <form method="POST" action="{{ route('swiss.demo.reset') }}"
                  onsubmit="return confirm('Reset all Swiss rounds for this tournament?');">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                <button type="submit" style="background:#b91c1c; margin-top:10px;">
                    Reset Swiss Rounds
                </button>
            </form>
        @endif

        @foreach($rounds as $round)
            <div class="round">
                <h2>{{ $round->name }}</h2>

                <table>
                    <tr>
                        <th>Match</th>
                        <th>Participant 1</th>
                        <th>Participant 2</th>
                        <th>Status</th>
                        <th>Result</th>
                    </tr>

                    @foreach($round->matches as $match)
                        <tr>
                            <td>{{ $match->match_number }}</td>

                            <td>
                                {{ $match->entry1?->display_name ?? '-' }}
                            </td>

                            <td>
                                @if($match->is_bye)
                                    <span class="bye">BYE</span>
                                @else
                                    {{ $match->entry2?->display_name ?? '-' }}
                                @endif
                            </td>

                            <td>{{ ucfirst($match->status) }}</td>

                            <td>
                                @if($match->is_bye)
                                    Bye awarded
                                @elseif($match->status === 'ready')
                                    <a href="{{ route('matches.result.create', $match) }}">
                                        Report Result
                                    </a>
                                @elseif($match->status === 'completed')
                                    {{ $match->score1 }} - {{ $match->score2 }}
                                @else
                                    {{ ucfirst($match->status) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif

</div>
</body>
</html>
