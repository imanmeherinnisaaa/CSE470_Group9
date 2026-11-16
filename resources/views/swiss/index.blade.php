<!DOCTYPE html>
<html>
<head>
    <title>F5 - Swiss Pairing Engine</title>
    <style>
        body { font-family: Arial; background: #f4f5f7; margin: 35px; }
        .container { max-width: 950px; margin: auto; background: white; padding: 25px; border-radius: 10px; }
        h1 { color: #243b53; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #edf2f7; }
        button { background: #2563eb; color: white; padding: 10px 18px; border: 0; border-radius: 5px; cursor: pointer; }
        input { padding: 8px; width: 85px; }
        .message { background: #dcfce7; padding: 12px; margin: 12px 0; }
        .error { color: #b91c1c; }
        .bye { background: #fef3c7; padding: 12px; margin-top: 15px; }
        a { margin-right: 15px; }
    </style>
</head>
<body>
<div class="container">

    <h1>F5 - Swiss Pairing Engine</h1>
    <p>Generate score-based pairings while avoiding previous opponents.</p>

    <p>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('seeding.demo') }}">Seeding (F3)</a>
    </p>

    <p><strong>Current round:</strong> {{ $roundNumber }}</p>

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

    <h2>Participants and scores</h2>

    <form method="POST" action="{{ route('swiss.demo.scores') }}">
        @csrf

        <table>
            <tr>
                <th>Participant</th>
                <th>Score</th>
                <th>Previous opponents</th>
                <th>Received bye?</th>
            </tr>

            @foreach($participants as $participant)
                <tr>
                    <td>{{ $participant['name'] }}</td>

                    <td>
                        <input
                            type="number"
                            step="0.5"
                            min="0"
                            max="1000"
                            name="scores[{{ $participant['id'] }}]"
                            value="{{ $participant['score'] }}"
                            required
                        >
                    </td>

                    <td>
                        {{ empty($participant['opponents'])
                            ? 'None'
                            : implode(', ', $participant['opponents']) }}
                    </td>

                    <td>
                        {{ $participant['has_received_bye'] ? 'Yes' : 'No' }}
                    </td>
                </tr>
            @endforeach
        </table>

        <br>
        <button type="submit">Update Scores</button>
    </form>

    <hr>

    <form method="POST" action="{{ route('swiss.demo.generate') }}">
        @csrf
        <button type="submit">Generate Next Round</button>
    </form>

    <form method="POST" action="{{ route('swiss.demo.reset') }}" style="margin-top: 12px;">
        @csrf
        <button type="submit" style="background: #dc2626;">Reset Demonstration</button>
    </form>

    @if($result)
        <h2>Round {{ $result['round_number'] }} Pairings</h2>

        <table>
            <tr>
                <th>Match</th>
                <th>Player 1</th>
                <th>Player 2</th>
                <th>Score difference</th>
                <th>Rematch?</th>
            </tr>

            @foreach($result['matches'] as $index => $match)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $match['player1']['name'] }}</td>
                    <td>{{ $match['player2']['name'] }}</td>
                    <td>{{ $match['score_difference'] }}</td>
                    <td>{{ $match['rematch'] ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </table>

        @if($result['bye'])
            <div class="bye">
                <strong>Bye recipient:</strong>
                {{ $result['bye']['name'] }}
            </div>
        @endif
    @endif

    <p style="margin-top: 25px; color: #666;">
        Demonstration mode: data is stored in your browser session.
        It is not yet saved to the shared tournament database.
    </p>

</div>
</body>
</html>
