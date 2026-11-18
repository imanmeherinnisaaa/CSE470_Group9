<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Tournament;
use App\Services\SeedingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SeedingController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::where('organizer_id', auth()->id())
            ->orderBy('name')
            ->get();

        $selectedId = request('tournament_id', session('seeding_tournament_id'));

        $tournament = $tournaments->first(
            fn ($item) => (int) $item->id === (int) $selectedId
        );

        $participants = $tournament
            ? $tournament->entries()
                ->confirmed()
                ->with(['user', 'team'])
                ->get()
                ->map(fn ($entry) => [
                    'id' => $entry->id,
                    'name' => $entry->team?->name ?? $entry->user?->name ?? 'Unknown',
                    'rating' => $entry->rating_snapshot ?? 0,
                    'team_id' => $entry->team_id,
                ])->all()
            : [];

        $result = session('seeding_demo_tournament_id') == $tournament?->id
            ? session('seeding_demo_result')
            : null;

        return view('seeding.index', compact(
            'tournaments',
            'tournament',
            'participants',
            'result'
        ));
    }

    public function generate(Request $request, SeedingService $service)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer',
            'method' => 'required|in:rating,random,manual',
            'separate_teammates' => 'nullable|boolean',
            'manual_order' => 'required_if:method,manual|nullable|string',
        ]);

        $tournament = Tournament::where('organizer_id', auth()->id())
            ->findOrFail($validated['tournament_id']);

        $entries = $tournament->entries()
            ->confirmed()
            ->with(['user', 'team'])
            ->get();

        $participants = $entries->map(fn ($entry) => [
            'id' => $entry->id,
            'name' => $entry->team?->name ?? $entry->user?->name ?? 'Unknown',
            'rating' => $entry->rating_snapshot ?? 0,
            'team_id' => $entry->team_id,
        ])->all();

        $separate = $request->boolean('separate_teammates');

        try {
            if ($validated['method'] === 'rating') {
                $result = $service->seedByRating($participants, $separate);
            } elseif ($validated['method'] === 'random') {
                $result = $service->seedRandomly($participants, $separate);
            } else {
                $manualOrder = array_map(
                    'intval',
                    array_filter(
                        array_map('trim', explode(',', $validated['manual_order'])),
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
            return back()
                ->withErrors(['manual_order' => $exception->getMessage()])
                ->withInput();
        }

        DB::transaction(function () use ($result, $tournament) {
            foreach ($result as $participant) {
                Entry::where('tournament_id', $tournament->id)
                    ->whereKey($participant['id'])
                    ->where('status', Entry::CONFIRMED)
                    ->update(['seed' => $participant['seed']]);
            }
        });

        session([
            'seeding_demo_result' => $result,
            'seeding_demo_tournament_id' => $tournament->id,
            'seeding_tournament_id' => $tournament->id,
        ]);

        return redirect()->route('seeding.demo', [
            'tournament_id' => $tournament->id,
        ])->with('status', 'Seeds generated and saved successfully.');
    }
}
