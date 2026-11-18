<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BracketServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTournament(string $format, int $players): Tournament
    {
        $organizer = User::factory()->create();
        $t = Tournament::create([
            'name' => 'Test Cup', 'game' => 'Chess', 'format' => $format,
            'min_participants' => 2, 'max_participants' => 128, 'best_of' => 1,
            'status' => 'draft', 'organizer_id' => $organizer->id,
        ]);
        for ($i = 1; $i <= $players; $i++) {
            Entry::forceCreate([
                'tournament_id' => $t->id,
                'user_id' => User::factory()->create()->id,
                'seed' => $i,
                'status' => Entry::CONFIRMED,
            ]);
        }

        return $t;
    }

    public function test_single_elimination_match_and_bye_counts(): void
    {
        // players => [total matches, byes]
        foreach ([5 => [7, 3], 8 => [7, 0], 13 => [15, 3]] as $players => [$matches, $byes]) {
            $t = $this->makeTournament('single_elimination', $players);
            app(BracketService::class)->generate($t);

            $this->assertSame($matches, $t->matches()->count(), "matches for {$players}");
            $this->assertSame($byes, $t->matches()->where('is_bye', true)->count(), "byes for {$players}");
        }
    }

    public function test_top_seeds_receive_the_byes(): void
    {
        $t = $this->makeTournament('single_elimination', 13);
        app(BracketService::class)->generate($t);

        $expected = $t->entries()->whereIn('seed', [1, 2, 3])->pluck('id')->all();
        $actual = $t->matches()->where('is_bye', true)->pluck('winner_entry_id')->all();

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function test_bye_winner_is_already_in_round_two(): void
    {
        $t = $this->makeTournament('single_elimination', 5);
        app(BracketService::class)->generate($t);

        $seed1 = $t->entries()->where('seed', 1)->value('id');
        $appearances = $t->matches()
            ->where(fn ($q) => $q->where('entry1_id', $seed1)->orWhere('entry2_id', $seed1))
            ->count();

        $this->assertSame(2, $appearances); // the bye match and the next round
    }

    public function test_double_elimination_match_count(): void
    {
        $t = $this->makeTournament('double_elimination', 8);
        app(BracketService::class)->generate($t);

        $this->assertSame(14, $t->matches()->count()); // 2n - 2 with no reset match
    }

    public function test_double_elimination_loser_drops_to_losers_bracket_and_bye_cascades(): void
    {
    $t = $this->makeTournament('double_elimination', 5);
    $service = app(BracketService::class);
    $service->generate($t);

    // Seed 4 v 5 (round 1) and seed 2 v 3 (round 2, both had byes) are playable
    $this->assertSame(2, $t->matches()->where('status', 'ready')->count());

    $match = $t->matches()
        ->where('status', 'ready')
        ->where('bracket', 'winners')
        ->whereHas('round', fn ($q) => $q->where('number', 1))
        ->firstOrFail();

    $winner = $match->entry1_id;
    $loser = $match->entry2_id;
    $service->advanceWinner($match, $winner);

    $lbMatch = GameMatch::find($match->loser_next_match_id);
    $this->assertSame('losers', $lbMatch->bracket);
    $this->assertSame('bye', $lbMatch->status);           // the other slot is empty
    $this->assertSame($loser, $lbMatch->winner_entry_id); // the loser advances automatically
    $this->assertSame(
        $loser,
        GameMatch::find($lbMatch->next_match_id)->{'entry' . $lbMatch->next_slot . '_id'}
    );
}
    public function test_regenerating_replaces_the_old_bracket(): void
    {
        $t = $this->makeTournament('single_elimination', 8);
        $service = app(BracketService::class);
        $service->generate($t);
        $service->generate($t);

        $this->assertSame(7, $t->matches()->count());
    }

    public function test_cannot_generate_for_swiss(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(BracketService::class)->generate($this->makeTournament('swiss', 8));
    }

    public function test_cannot_generate_with_too_few_entries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(BracketService::class)->generate($this->makeTournament('double_elimination', 3));
    }
}