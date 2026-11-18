<?php

namespace App\Http\Controllers;

use App\Services\SwissPairingService;
use Illuminate\Http\Request;

class SwissController extends Controller
{
    private function participants(): array
    {
        return session('swiss_demo_participants', [
            [
                'id' => 1,
                'name' => 'Rahim',
                'score' => 3,
                'opponents' => [2],
                'has_received_bye' => false,
            ],
            [
                'id' => 2,
                'name' => 'Karim',
                'score' => 3,
                'opponents' => [1],
                'has_received_bye' => false,
            ],
            [
                'id' => 3,
                'name' => 'Sara',
                'score' => 1,
                'opponents' => [],
                'has_received_bye' => false,
            ],
            [
                'id' => 4,
                'name' => 'Nadia',
                'score' => 1,
                'opponents' => [],
                'has_received_bye' => false,
            ],
            [
                'id' => 5,
                'name' => 'Tania',
                'score' => 0,
                'opponents' => [],
                'has_received_bye' => false,
            ],
        ]);
    }

    public function index()
    {
        return view('swiss.index', [
            'participants' => $this->participants(),
            'result' => session('swiss_demo_result'),
            'roundNumber' => session('swiss_demo_round', 0),
        ]);
    }

    public function generate(SwissPairingService $service)
    {
        $participants = $this->participants();

        try {
            $result = $service->generatePairings($participants);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'pairing' => $exception->getMessage(),
            ]);
        }

        // Remember the opponents so future rounds can avoid rematches.
        $indexes = [];

        foreach ($participants as $index => $participant) {
            $indexes[(string) $participant['id']] = $index;
        }

        foreach ($result['matches'] as $match) {
            $firstId = (string) $match['player1']['id'];
            $secondId = (string) $match['player2']['id'];

            $firstIndex = $indexes[$firstId];
            $secondIndex = $indexes[$secondId];

            $participants[$firstIndex]['opponents'][] =
                $match['player2']['id'];

            $participants[$secondIndex]['opponents'][] =
                $match['player1']['id'];
        }

        // Remember the bye so the same player is not selected again.
        if ($result['bye'] !== null) {
            $byeId = (string) $result['bye']['id'];
            $byeIndex = $indexes[$byeId];

            $participants[$byeIndex]['has_received_bye'] = true;
        }

        $roundNumber = session('swiss_demo_round', 0) + 1;

        $result['round_number'] = $roundNumber;

        session([
            'swiss_demo_participants' => $participants,
            'swiss_demo_result' => $result,
            'swiss_demo_round' => $roundNumber,
        ]);

        return redirect()->route('swiss.demo')
            ->with('status', 'Round ' . $roundNumber . ' pairings generated.');
    }

    public function updateScores(Request $request)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|numeric|min:0|max:1000',
        ]);

        $participants = $this->participants();

        foreach ($participants as &$participant) {
            $id = (string) $participant['id'];

            if (isset($validated['scores'][$id])) {
                $participant['score'] = (float) $validated['scores'][$id];
            }
        }

        unset($participant);

        session(['swiss_demo_participants' => $participants]);

        return redirect()->route('swiss.demo')
            ->with('status', 'Scores updated.');
    }

    public function reset()
    {
        session()->forget([
            'swiss_demo_participants',
            'swiss_demo_result',
            'swiss_demo_round',
        ]);

        return redirect()->route('swiss.demo')
            ->with('status', 'Swiss demonstration reset.');
    }
}
