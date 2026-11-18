<?php

namespace Tests\Feature;

use App\Exceptions\RosterException;
use App\Models\Entry;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\RosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sample cases from SRS section 8 for F13: exceed roster size, edit during a lock window,
 * add an ineligible substitute. Adjust the Tournament columns in tournament() to match F1's migration.
 */
class RosterServiceTest extends TestCase
{
    use RefreshDatabase;

    private RosterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['roster.max_starters' => 2, 'roster.max_substitutes' => 1]);
        $this->service = app(RosterService::class);
    }

    private function player(int $rating = 1000, string $role = 'player'): User
    {
        return User::factory()->create(['role' => $role, 'rating' => $rating]);
    }

    private function team(?User $captain = null): Team
    {
        return $this->service->createTeam($captain ?? $this->player(), 'Team ' . uniqid());
    }

    private function tournament(array $overrides = []): Tournament
    {
        return Tournament::forceCreate(array_merge([
            'name' => 'Test Cup',
            'format' => 'single_elimination',
            'max_participants' => 8,
            'status' => 'registration',
            'entry_type' => 'team',
            'team_size' => 2,
            'entry_opens_at' => now()->subDay(),
            'entry_closes_at' => now()->addDay(),
        ], $overrides));
    }

    private function enter(Team $team, Tournament $t, string $status = Entry::CONFIRMED): Entry
    {
        return Entry::forceCreate([
            'tournament_id' => $t->id, 'team_id' => $team->id, 'status' => $status, 'entered_at' => now(),
        ]);
    }

    private function assertRejected(callable $action, string $needle): void
    {
        try {
            $action();
            $this->fail('Expected a RosterException containing: ' . $needle);
        } catch (RosterException $e) {
            $this->assertStringContainsString($needle, $e->getMessage());
        }
    }

    // ---- team creation

    public function test_creating_a_team_makes_the_creator_captain_and_first_starter(): void
    {
        $user = $this->player();
        $team = $this->service->createTeam($user, 'Night Owls');

        $this->assertSame($user->id, $team->captain_id);
        $this->assertSame(1, $team->starters()->count());
        $this->assertSame('team_captain', $user->fresh()->role);
    }

    public function test_a_captain_cannot_lead_two_teams(): void
    {
        $user = $this->player();
        $this->service->createTeam($user, 'First');

        $this->assertRejected(fn () => $this->service->createTeam($user->fresh(), 'Second'), 'only one team');
    }

    public function test_organizers_cannot_create_teams(): void
    {
        $this->assertRejected(fn () => $this->service->createTeam($this->player(1000, 'organizer'), 'Nope'), 'Only players');
    }

    // ---- size limits

    public function test_starter_limit_is_enforced(): void
    {
        $team = $this->team(); // captain is starter 1 of 2
        $this->service->addPlayer($team, $this->player()->email);

        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player()->email), 'at most 2 starters');
    }

    public function test_substitute_limit_is_enforced(): void
    {
        $team = $this->team();
        $this->service->addPlayer($team, $this->player()->email, true);

        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player()->email, true), 'at most 1 substitutes');
    }

    public function test_the_same_player_cannot_be_added_twice(): void
    {
        $team = $this->team();
        $p = $this->player();
        $this->service->addPlayer($team, $p->email, true);

        $this->assertRejected(fn () => $this->service->addPlayer($team, $p->email), 'already on this roster');
    }

    public function test_unknown_email_and_non_player_accounts_are_rejected(): void
    {
        $team = $this->team();

        $this->assertRejected(fn () => $this->service->addPlayer($team, 'nobody@example.com'), 'No account');
        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player(1000, 'organizer')->email), 'player account');
    }

    // ---- eligibility

    public function test_a_substitute_outside_the_tournament_rating_range_is_rejected(): void
    {
        $team = $this->team();
        $this->service->addPlayer($team, $this->player()->email);
        $this->enter($team, $this->tournament(['min_rating' => 1200]));

        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player(1000)->email, true), 'below the minimum of 1200');

        $ok = $this->service->addPlayer($team, $this->player(1300)->email, true);
        $this->assertTrue($ok->is_substitute);
    }

    public function test_a_player_on_another_team_entered_in_the_same_tournament_is_rejected(): void
    {
        $t = $this->tournament();
        $teamA = $this->team();
        $teamB = $this->team();
        $shared = $this->player();
        $this->service->addPlayer($teamA, $shared->email, true);
        $this->enter($teamA, $t);
        $this->enter($teamB, $t);

        $this->assertRejected(fn () => $this->service->addPlayer($teamB, $shared->email, true), 'another team entered');
    }

    // ---- lock window

    public function test_every_edit_is_rejected_while_the_tournament_is_running(): void
    {
        $team = $this->team();
        $starter = $this->service->addPlayer($team, $this->player()->email);
        $sub = $this->service->addPlayer($team, $this->player()->email, true);
        $this->enter($team, $this->tournament(['status' => 'in_progress']));

        $this->assertTrue($team->fresh()->isLocked());
        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player()->email, true), 'locked');
        $this->assertRejected(fn () => $this->service->removePlayer($team, $sub), 'locked');
        $this->assertRejected(fn () => $this->service->substitute($team, $starter->id, $sub->id), 'locked');
        $this->assertRejected(fn () => $this->service->renameTeam($team, 'New name'), 'locked');
    }

    public function test_the_roster_locks_at_the_lock_time_during_registration(): void
    {
        $team = $this->team();
        $t = $this->tournament(['roster_lock_at' => now()->addHour()]);
        $this->enter($team, $t);

        $this->assertFalse($team->fresh()->isLocked());
        $this->service->addPlayer($team, $this->player()->email, true); // still open

        $t->forceFill(['roster_lock_at' => now()->subMinute()])->save();
        $this->assertTrue($team->fresh()->isLocked());
        $this->assertRejected(fn () => $this->service->addPlayer($team, $this->player()->email), 'locked');
    }

    public function test_a_finished_tournament_no_longer_locks_the_roster(): void
    {
        $team = $this->team();
        $this->enter($team, $this->tournament(['status' => 'completed']));

        $this->assertFalse($team->fresh()->isLocked());
    }

    public function test_sync_locks_mirrors_the_lock_state_on_slots(): void
    {
        $team = $this->team();
        $t = $this->tournament(['status' => 'in_progress']);
        $this->enter($team, $t);

        $this->service->syncLocks($team);
        $this->assertSame(1, $team->rosterSlots()->where('locked', true)->count());

        $t->forceFill(['status' => 'completed'])->save();
        $this->service->syncLocks($team);
        $this->assertSame(0, $team->rosterSlots()->where('locked', true)->count());
    }

    // ---- removal and substitution

    public function test_the_captain_cannot_be_removed(): void
    {
        $team = $this->team();
        $captainSlot = $team->rosterSlots()->first();

        $this->assertRejected(fn () => $this->service->removePlayer($team, $captainSlot), 'captain cannot be removed');
    }

    public function test_removing_a_starter_is_blocked_when_it_would_break_the_tournament_team_size(): void
    {
        $team = $this->team();
        $slot = $this->service->addPlayer($team, $this->player()->email);
        $this->enter($team, $this->tournament(['team_size' => 2]));

        $this->assertRejected(fn () => $this->service->removePlayer($team, $slot), 'fewer than 2 starters');
    }

    public function test_removing_a_player_works_when_the_team_is_not_entered(): void
    {
        $team = $this->team();
        $slot = $this->service->addPlayer($team, $this->player()->email);

        $this->service->removePlayer($team, $slot);

        $this->assertSame(1, $team->starters()->count());
    }

    public function test_a_substitution_swaps_a_starter_with_a_substitute(): void
    {
        $team = $this->team();
        $starter = $this->service->addPlayer($team, $this->player()->email);
        $sub = $this->service->addPlayer($team, $this->player()->email, true);
        $this->enter($team, $this->tournament(['status' => 'registration']));

        $this->service->substitute($team, $starter->id, $sub->id);

        $this->assertTrue($starter->fresh()->is_substitute);
        $this->assertFalse($sub->fresh()->is_substitute);
        $this->assertSame(2, $team->starters()->count()); // starter count unchanged
    }

    public function test_a_substitution_needs_one_starter_and_one_substitute_from_this_team(): void
    {
        $team = $this->team();
        $starter = $this->service->addPlayer($team, $this->player()->email);
        $otherTeamSub = $this->service->addPlayer($this->team(), $this->player()->email, true);

        $this->assertRejected(fn () => $this->service->substitute($team, $starter->id, $otherTeamSub->id), 'one starter and one substitute');
        $this->assertRejected(fn () => $this->service->substitute($team, $starter->id, $starter->id), 'one starter and one substitute');
    }

    // ---- delete

    public function test_a_team_with_an_active_entry_cannot_be_deleted(): void
    {
        $team = $this->team();
        $this->enter($team, $this->tournament());

        $this->assertRejected(fn () => $this->service->deleteTeam($team), 'Withdraw the entry');
    }

    public function test_deleting_a_team_removes_its_roster_and_returns_the_captain_to_player(): void
    {
        $captain = $this->player();
        $team = $this->team($captain);
        $this->service->addPlayer($team, $this->player()->email, true);

        $this->service->deleteTeam($team);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('roster_slots', ['team_id' => $team->id]);
        $this->assertSame('player', $captain->fresh()->role);
    }

    // ---- HTTP authorization (needs the routes from README_F13.md)

    public function test_only_the_captain_can_change_the_roster_over_http(): void
    {
        $team = $this->team();
        $target = $this->player();
        $stranger = $this->player();

        $this->actingAs($stranger)
            ->post(route('teams.roster.add', $team), ['email' => $target->email, 'slot_type' => 'substitute'])
            ->assertForbidden();

        $this->actingAs($team->captain)
            ->post(route('teams.roster.add', $team), ['email' => $target->email, 'slot_type' => 'substitute'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, $team->substitutes()->count());
    }

    public function test_a_rule_violation_over_http_comes_back_as_an_error_message(): void
    {
        $team = $this->team();
        $this->service->addPlayer($team, $this->player()->email); // starters now full (2 of 2)

        $this->actingAs($team->captain)
            ->post(route('teams.roster.add', $team), ['email' => $this->player()->email, 'slot_type' => 'starter'])
            ->assertSessionHasErrors('roster');
    }
}
