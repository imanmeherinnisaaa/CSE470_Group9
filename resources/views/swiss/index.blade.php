<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F5 - Swiss Pairing Engine | Tournament Management</title>

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

        h2 {
            font-size: 19px;
            color: #172554;
            margin: 0;
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

        select {
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

        select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
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

        .btn-danger {
            background: #dc2626;
        }

        .btn-danger:hover {
            background: #991b1b;
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

        .rank {
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

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-box {
            background: #f8faff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 17px;
        }

        .stat-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .stat-value {
            color: #172554;
            font-size: 23px;
            font-weight: 700;
        }

        .bye {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .match-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }

        .match-link:hover {
            text-decoration: underline;
        }

        .round {
            margin-top: 20px;
        }

        .round-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .round-number {
            background: #eef2ff;
            color: #4338ca;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 18px;
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

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">TOURNAMENT MANAGEMENT</div>

    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('seeding.demo') }}">Seeding Engine (F3)</a>
    </nav>
</header>

<main class="container">

    <div class="page-heading">
        <div class="eyebrow">Tournament Administration / F5</div>
        <h1>Swiss Pairing Engine</h1>
        <p class="subtitle">
            Generate score-based rounds, avoid rematches, and track tournament standings.
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
                <h2>Select Swiss Tournament</h2>
                <p class="description">
                    Choose a Swiss-format tournament to manage its rounds and standings.
                </p>
            </div>
            <span class="section-tag">TOURNAMENT</span>
        </div>

        <form method="GET" action="{{ route('swiss.demo') }}">
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

                <button type="submit" class="btn">Load Tournament</button>
            </div>
        </form>

        @if($tournaments->isEmpty())
            <div class="empty-state" style="margin-top:18px;">
                No Swiss-format tournaments were found under your account.
            </div>
        @endif
    </section>

    @if($tournament)

        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>{{ $tournament->name }}</h2>
                    <p class="description">
                        Swiss tournament overview
                    </p>
                </div>
                <span class="section-tag">ACTIVE VIEW</span>
            </div>

            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Confirmed Entries</div>
                    <div class="stat-value">{{ $entries->count() }}</div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Configured Rounds</div>
                    <div class="stat-value">{{ $tournament->swiss_rounds ?? '—' }}</div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>Current Standings</h2>
                    <p class="description">
                        Participants ranked according to their current tournament points.
                    </p>
                </div>
                <span class="section-tag">LEADERBOARD</span>
            </div>

            @if(empty($standings))
                <div class="empty-state">
                    No confirmed entries are available.
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Seed</th>
                                <th>Participant / Team</th>
                                <th>Points</th>
                                <th>Bye Received</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($standings as $index => $participant)
                                <tr>
                                    <td>
                                        <span class="rank">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>{{ $participant['seed'] ?? '-' }}</td>

                                    <td>
                                        <strong>{{ $participant['name'] }}</strong>
                                    </td>

                                    <td>
                                        <strong>{{ $participant['score'] }}</strong>
                                    </td>

                                    <td>
                                        @if($participant['has_received_bye'])
                                            <span class="bye">Yes</span>
                                        @else
                                            No
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>Round Generation</h2>
                    <p class="description">
                        Create the next Swiss round using current standings.
                    </p>
                </div>
                <span class="section-tag">PAIRING ENGINE</span>
            </div>

            <div class="stat-box">
                <div class="stat-label">Next Round</div>
                <div class="stat-value">{{ $nextRound }}</div>
            </div>

            <form method="POST" action="{{ route('swiss.demo.generate') }}">
                @csrf

                <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                <div class="actions">
                    <button type="submit" class="btn">
                        Generate and Save Round {{ $nextRound }} →
                    </button>
                </div>
            </form>

            @if($rounds->isNotEmpty())
                <form method="POST" action="{{ route('swiss.demo.reset') }}"
                      onsubmit="return confirm('Reset all Swiss rounds for this tournament?');">

                    @csrf

                    <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                    <div class="actions">
                        <button type="submit" class="btn btn-danger">
                            Reset Swiss Rounds
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <section class="card">
            <div class="card-heading">
                <div>
                    <h2>Generated Rounds & Match Pairings</h2>
                    <p class="description">
                        View the matches, participants, and results for each generated round.
                    </p>
                </div>
                <span class="section-tag">MATCH HISTORY</span>
            </div>

            @if($rounds->isEmpty())
                <div class="empty-state">
                    No rounds have been generated yet.
                </div>
            @else

                @foreach($rounds as $round)
                    <div class="round">

                        <div class="round-title">
                            <h2>{{ $round->name }}</h2>
                            <span class="round-number">SWISS ROUND</span>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Match</th>
                                        <th>Participant 1</th>
                                        <th>Participant 2</th>
                                        <th>Status</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($round->matches as $match)
                                        <tr>
                                            <td>
                                                <strong>{{ $match->match_number }}</strong>
                                            </td>

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

                                            <td>
                                                @if($match->status === 'completed')
                                                    <span class="status status-completed">
                                                        Completed
                                                    </span>
                                                @else
                                                    <span class="status">
                                                        {{ ucfirst($match->status) }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if($match->is_bye)
                                                    <span class="bye">Bye awarded</span>

                                                @elseif($match->status === 'ready')
                                                    <a class="match-link"
                                                       href="{{ route('matches.result.create', $match) }}">
                                                        Report Result →
                                                    </a>

                                                @elseif($match->status === 'completed')
                                                    <strong>
                                                        {{ $match->score1 }} - {{ $match->score2 }}
                                                    </strong>

                                                @else
                                                    {{ ucfirst($match->status) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach

            @endif
        </section>

    @endif

    <div class="footer">
        Tournament Management System · F5 Swiss Pairing Engine
    </div>

</main>

</body>
</html>

