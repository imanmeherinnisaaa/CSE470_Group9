<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\RosterSlot;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\TeammateGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeammateGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeammateGroupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TeammateGroupService::class);
    }

    private function tournament(string $type = 'solo'): Tournament
    {
        return Tournament::forceCreate([
            'name' => 'Seed Cup', 'format' => 'single_elimination', 'max_participants' => 16,
            'status' => 'registration', 'entry_type' => $type, 'team_size' => $type === 'team' ? 2 : null,
        ]);
    }

    private function player(): User
    {
        return User::factory()->create(['role' => 'player', 'rating' => 1000]);
    }

    private function soloEntry(Tournament $t, User $u, string $status = Entry::CONFIRMED): Entry
    {
        return Entry::forceCreate(['tournament_id' => $t->id, 'user_id' => $u->id, 'status' => $status, 'entered_at' => now()]);
    }

    /** First player is the captain; later players are substitutes unless $starters says otherwise. */
    private function team(array $players): Team
    {
        $team = Team::forceCreate(['name' => 'T' . uniqid(), 'captain_id' => $players[0]->id]);
        foreach ($players as $i => $p) {
            RosterSlot::forceCreate(['team_id' => $team->id, 'player_id' => $p->id, 'is_substitute' => $i > 1]);
        }
        return $team;
    }

    public function test_players_on_the_same_team_share_a_group_and_others_do_not(): void
    {
        $t = $this->tournament();
        [$a, $b, $c] = [$this->player(), $this->player(), $this->player()];
        $this->team([$a, $b]);
        $ea = $this->soloEntry($t, $a);
        $eb = $this->soloEntry($t, $b);
        $ec = $this->soloEntry($t, $c);

        $g = $this->service->groupsFor($t);

        $this->assertSame($g[$ea->id], $g[$eb->id]);
        $this->assertNotSame($g[$ea->id], $g[$ec->id]);
    }

    public function test_grouping_is_transitive_across_teams(): void
    {
        $t = $this->tournament();
        [$a, $b, $c] = [$this->player(), $this->player(), $this->player()];
        $this->team([$a, $b]);
        $this->team([$b, $c]);
        $ea = $this->soloEntry($t, $a);
        $ec = $this->soloEntry($t, $c);
        $eb = $this->soloEntry($t, $b);

        $g = $this->service->groupsFor($t);

        $this->assertSame($g[$ea->id], $g[$ec->id]);
        $this->assertSame($g[$ea->id], $g[$eb->id]);
    }

    public function test_a_substitute_counts_as_a_teammate(): void
    {
        $t = $this->tournament();
        [$a, $b, $sub] = [$this->player(), $this->player(), $this->player()];
        $this->team([$a, $b, $sub]); // $sub is a substitute slot
        $ea = $this->soloEntry($t, $a);
        $es = $this->soloEntry($t, $sub);

        $g = $this->service->groupsFor($t);

        $this->assertSame($g[$ea->id], $g[$es->id]);
    }

    public function test_waitlisted_and_withdrawn_entries_are_left_out(): void
    {
        $t = $this->tournament();
        [$a, $b, $c] = [$this->player(), $this->player(), $this->player()];
        $ea = $this->soloEntry($t, $a);
        $eb = $this->soloEntry($t, $b, Entry::WAITLISTED);
        $ec = $this->soloEntry($t, $c, Entry::WITHDRAWN);

        $g = $this->service->groupsFor($t);

        $this->assertArrayHasKey($ea->id, $g);
        $this->assertArrayNotHasKey($eb->id, $g);
        $this->assertArrayNotHasKey($ec->id, $g);
    }

    public function test_in_a_team_tournament_every_entry_is_its_own_group(): void
    {
        $t = $this->tournament('team');
        $teamA = $this->team([$this->player(), $this->player()]);
        $teamB = $this->team([$this->player(), $this->player()]);
        $ea = Entry::forceCreate(['tournament_id' => $t->id, 'team_id' => $teamA->id, 'status' => Entry::CONFIRMED, 'entered_at' => now()]);
        $eb = Entry::forceCreate(['tournament_id' => $t->id, 'team_id' => $teamB->id, 'status' => Entry::CONFIRMED, 'entered_at' => now()]);

        $g = $this->service->groupsFor($t);

        $this->assertNotSame($g[$ea->id], $g[$eb->id]);
    }
}
