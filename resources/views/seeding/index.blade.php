<!DOCTYPE html>
<html>
<head>
    <title>F3 - Seeding Engine</title>
    <style>
        body { font-family: Arial; background: #f4f5f7; margin: 35px; }
        .container { max-width: 950px; margin: auto; background: white; padding: 25px; border-radius: 10px; }
        h1 { color: #243b53; }
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
    <p>Generate tournament seeds using different methods.</p>

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

    <h2>Available demo participants</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Rating</th>
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

    <h2>Generate seeds</h2>

    <form method="POST" action="{{ route('seeding.demo.generate') }}">
        @csrf

        <label>Seeding method:</label>
        <br>

        <select name="method" required>
            <option value="rating">Rating-based</option>
            <option value="random">Random</option>
            <option value="manual">Manual order</option>
        </select>

        <br>

        <label>Manual order (participant IDs separated by commas):</label>
        <br>

        <input
            type="text"
            name="manual_order"
            placeholder="Example: 3,1,5,2,4"
            style="width: 300px;"
        >

        <br>

        <label>
            <input type="checkbox" name="separate_teammates" value="1">
            Distribute teammates across the seed order
        </label>

        <br><br>

        <button type="submit">Generate Seeds</button>
    </form>

    @if($result)
        <h2>Generated seed order</h2>

        <table>
            <tr>
                <th>Seed</th>
                <th>Participant</th>
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
