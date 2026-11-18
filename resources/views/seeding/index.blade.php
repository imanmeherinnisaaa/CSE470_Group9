<!DOCTYPE html>
<html>
<head>
    <title>F3 - Seeding Engine</title>
    <style>
        body { font-family: Arial; background: #f4f5f7; margin: 35px; }
        .container { max-width: 950px; margin: auto; background: white; padding: 25px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #edf2f7; }
        button { background: #2563eb; color: white; padding: 10px 18px; border: 0; border-radius: 5px; cursor: pointer; }
        input, select { padding: 9px; margin: 8px 0; }
        .message { background: #dcfce7; padding: 12px; margin: 12px 0; }
        .error { color: #b91c1c; }
        a { margin-right: 15px; }
    </style>
</head>
<body>
<div class="container">

    <h1>F3 - Seeding Engine</h1>
    <p>Generate seeds for confirmed tournament entries.</p>

    <p>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('swiss.demo') }}">Swiss Pairing (F5)</a>
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

    <h2>Select Tournament</h2>

    <form method="GET" action="{{ route('seeding.demo') }}">
        <select name="tournament_id" required>
            <option value="">Choose a tournament</option>
            @foreach($tournaments as $item)
                <option value="{{ $item->id }}"
                    {{ $tournament?->id == $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
        <button type="submit">Load Entries</button>
    </form>

    @if($tournaments->isEmpty())
        <p>No tournaments created under your account.</p>
    @endif

    @if($tournament)
        <h2>Confirmed Participants - {{ $tournament->name }}</h2>

        @if(empty($participants))
            <p>No confirmed entries are available for seeding.</p>
        @else
            <table>
                <tr>
                    <th>Entry ID</th>
                    <th>Participant / Team</th>
                    <th>Rating Snapshot</th>
                    <th>Team ID</th>
                </tr>
                @foreach($participants as $participant)
                    <tr>
                        <td>{{ $participant['id'] }}</td>
                        <td>{{ $participant['name'] }}</td>
                        <td>{{ $participant['rating'] }}</td>
                        <td>{{ $participant['team_id'] ?? 'Individual' }}</td>
                    </tr>
                @endforeach
            </table>

            <h2>Generate Seeds</h2>

            <form method="POST" action="{{ route('seeding.demo.generate') }}">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                <label>Seeding Method:</label><br>
                <select name="method" required>
                    <option value="rating">Rating-based</option>
                    <option value="random">Random</option>
                    <option value="manual">Manual Order</option>
                </select>

                <br>

                <label>Manual order (Entry IDs separated by commas):</label><br>
                <input type="text" name="manual_order"
                    placeholder="Example: 5,2,1,4,3" style="width: 300px;">

                <br>

                <label>
                    <input type="checkbox" name="separate_teammates" value="1">
                    Distribute teammates across the seed order
                </label>

                <br><br>
                <button type="submit">Generate and Save Seeds</button>
            </form>
        @endif
    @endif

    @if($result)
        <h2>Generated Seed Order</h2>
        <table>
            <tr>
                <th>Seed</th>
                <th>Participant / Team</th>
                <th>Rating</th>
                <th>Team ID</th>
            </tr>
            @foreach($result as $participant)
                <tr>
                    <td>{{ $participant['seed'] }}</td>
                    <td>{{ $participant['name'] }}</td>
                    <td>{{ $participant['rating'] }}</td>
                    <td>{{ $participant['team_id'] ?? 'Individual' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

</div>
</body>
</html>
