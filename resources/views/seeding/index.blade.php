<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F3 - Seeding Engine | Tournament Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fb;
            color: #1e293b;
        }

        .topbar {
            background: #172554;
            color: white;
            padding: 17px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .topbar nav {
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
        }

        .topbar a {
            color: #dbeafe;
            text-decoration: none;
            font-size: 14px;
        }

        .topbar a:hover {
            color: white;
        }

        .container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 22px 40px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .eyebrow {
            color: #4f46e5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 30px;
            margin: 8px 0;
            color: #172554;
        }

        .subtitle {
            color: #64748b;
            margin: 0;
            line-height: 1.6;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
        }

        .card-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        h2 {
            font-size: 19px;
            color: #172554;
            margin: 0;
        }

        .description {
            font-size: 13px;
            color: #64748b;
            margin: 7px 0 0;
        }

        .section-tag {
            background: #eef2ff;
            color: #4338ca;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .form-row {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        input[type="text"], select {
            width: 100%;
            max-width: 420px;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
            color: #1e293b;
            font-size: 14px;
            outline: none;
        }

        input:focus, select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        .field {
            margin-bottom: 18px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 7px;
            padding: 11px 18px;
            background: #4f46e5;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: .2s;
        }

        .btn:hover {
            background: #3730a3;
        }

        .btn-secondary {
            background: #334155;
        }

        .btn-secondary:hover {
            background: #1e293b;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            text-align: left;
            padding: 14px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #edf0f5;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f8faff;
        }

        .number {
            color: #4f46e5;
            font-weight: 700;
        }

        .message {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            padding: 25px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .checkbox-row label {
            margin: 0;
            font-weight: 500;
        }

        .checkbox-row input {
            width: 16px;
            height: 16px;
            accent-color: #4f46e5;
        }

        .result-row td:first-child {
            font-weight: 700;
            color: #4f46e5;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 15px;
        }

        @media (max-width: 600px) {
            .container {
                margin-top: 22px;
                padding: 0 14px 30px;
            }

            .card {
                padding: 18px;
            }

            h1 {
                font-size: 25px;
            }

            .form-row {
                align-items: stretch;
            }

            .form-row select,
            .form-row button {
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">TOURNAMENT MANAGEMENT</div>

    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('swiss.demo') }}">Swiss Pairing (F5)</a>
    </nav>
</header>

<main class="container">

    <div class="page-heading">
        <div class="eyebrow">Tournament Administration / F3</div>
        <h1>Seeding Engine</h1>
        <p class="subtitle">
            Organize confirmed tournament participants into a fair starting order.
        </p>
    </div>

    @if(session('status'))
        <div class="message">
            ✓ {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="error">
            <strong>Please check the following:</strong>
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="card">
        <div class="card-heading">
            <div>
                <h2>Select Tournament</h2>
                <p class="description">Choose a tournament to view its confirmed participants.</p>
            </div>
            <span class="section-tag">STEP 01</span>
        </div>

        <form method="GET" action="{{ route('seeding.demo') }}">
            <div class="form-row">
                <select name="tournament_id" required>
                    <option value="">Choose a tournament</option>
                    @foreach($tournaments as $item)
                        <option value="{{ $item->id }}"
                            {{ $tournament?->id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn">Load Participants</button>
            </div>
        </form>

        @if($tournaments->isEmpty())
            <div class="empty-state" style="margin-top:18px;">
                No tournaments created under your account.
            </div>
        @endif
    </section>

    @if($tournament)
        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>Confirmed Participants</h2>
                    <p class="description">
                        Tournament: <strong>{{ $tournament->name }}</strong>
                    </p>
                </div>
                <span class="section-tag">PARTICIPANT LIST</span>
            </div>

            @if(empty($participants))
                <div class="empty-state">
                    No confirmed entries are available for seeding.
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Entry ID</th>
                                <th>Participant / Team</th>
                                <th>Rating Snapshot</th>
                                <th>Team ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($participants as $participant)
                                <tr>
                                    <td>{{ $participant['id'] }}</td>
                                    <td><strong>{{ $participant['name'] }}</strong></td>
                                    <td>{{ $participant['rating'] }}</td>
                                    <td>{{ $participant['team_id'] ?? 'Individual' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if(!empty($participants))
            <section class="card">
                <div class="card-heading">
                    <div>
                        <h2>Generate Seeds</h2>
                        <p class="description">
                            Select a seeding method and configure the starting order.
                        </p>
                    </div>
                    <span class="section-tag">STEP 02</span>
                </div>

                <form method="POST" action="{{ route('seeding.demo.generate') }}">
                    @csrf

                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                    <div class="field">
                        <label for="method">Seeding Method</label>
                        <select name="method" id="method" required>
                            <option value="rating">Rating-based</option>
                            <option value="random">Random</option>
                            <option value="manual">Manual Order</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="manual_order">
                            Manual Order (Entry IDs separated by commas)
                        </label>
                        <input
                            type="text"
                            id="manual_order"
                            name="manual_order"
                            placeholder="Example: 5, 2, 1, 4, 3"
                        >
                        <p class="description">
                            Only required when Manual Order is selected.
                        </p>
                    </div>

                    <div class="checkbox-row">
                        <input
                            type="checkbox"
                            id="separate_teammates"
                            name="separate_teammates"
                            value="1"
                        >
                        <label for="separate_teammates">
                            Distribute teammates across the seed order
                        </label>
                    </div>

                    <button type="submit" class="btn">
                        Generate and Save Seeds →
                    </button>
                </form>
            </section>
        @endif
    @endif

    @if($result)
        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>Generated Seed Order</h2>
                    <p class="description">
                        The following order has been generated and saved.
                    </p>
                </div>
                <span class="section-tag">RESULT</span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Seed Position</th>
                            <th>Participant / Team</th>
                            <th>Rating</th>
                            <th>Team ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result as $participant)
                            <tr class="result-row">
                                <td>
                                    <span class="number">#{{ $participant['seed'] }}</span>
                                </td>
                                <td><strong>{{ $participant['name'] }}</strong></td>
                                <td>{{ $participant['rating'] }}</td>
                                <td>{{ $participant['team_id'] ?? 'Individual' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="description" style="margin-top:16px;">
                Seed positions represent the initial tournament order, not the final results.
            </p>
        </section>
    @endif

    <div class="footer">
        Tournament Management System · F3 Seeding Engine
    </div>

</main>

</body>
</html>
