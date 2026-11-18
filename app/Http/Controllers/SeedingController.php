<?php

namespace App\Http\Controllers;

use App\Services\SeedingService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SeedingController extends Controller
{
    private function participants(): array
    {
        return [
            ['id' => 1, 'name' => 'Rahim', 'rating' => 2100, 'team_id' => 10],
            ['id' => 2, 'name' => 'Karim', 'rating' => 1900, 'team_id' => 10],
            ['id' => 3, 'name' => 'Sara', 'rating' => 1700, 'team_id' => 20],
            ['id' => 4, 'name' => 'Nadia', 'rating' => 1500, 'team_id' => 20],
            ['id' => 5, 'name' => 'Tania', 'rating' => 1400, 'team_id' => null],
        ];
    }

    public function index()
    {
        return view('seeding.index', [
            'participants' => $this->participants(),
            'result' => session('seeding_demo_result'),
        ]);
    }

    public function generate(
        Request $request,
        SeedingService $service
    ) {
        $validated = $request->validate([
            'method' => 'required|in:rating,random,manual',
            'separate_teammates' => 'nullable|boolean',
            'manual_order' => 'nullable|string',
        ]);

        $participants = $this->participants();
        $separate = $request->boolean('separate_teammates');

        try {
            if ($validated['method'] === 'rating') {
                $result = $service->seedByRating(
                    $participants,
                    $separate
                );
            } elseif ($validated['method'] === 'random') {
                $result = $service->seedRandomly(
                    $participants,
                    $separate
                );
            } else {
                $manualOrder = array_map(
                    'intval',
                    array_filter(
                        array_map(
                            'trim',
                            explode(',', $validated['manual_order'] ?? '')
                        ),
                        fn ($value) => $value !== ''
                    )
                );

                $result = $service->seedManually(
                    $participants,
                    $manualOrder,
                    $separate
                );
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'manual_order' => $exception->getMessage(),
            ]);
        }

        session(['seeding_demo_result' => $result]);

        return redirect()->route('seeding.demo')
            ->with('status', 'Seeding generated successfully.');
    }
}
