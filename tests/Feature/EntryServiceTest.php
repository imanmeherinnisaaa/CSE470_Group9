<?php

namespace Tests\Feature;

use App\Exceptions\EntryException;
use App\Models\Entry;
use App\Models\RosterSlot;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\EntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sample cases from SRS section 8 for F2.
 * Adjust the required Tournament columns in tournament() to match F1's migration.
 */
class EntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private EntryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EntryService::class);
    }

    private function tournament(array $overrides = []): Tournament
    {
        return Tournament::forceCreate(array_merge([
            'name' => 'Test Cup',
            'format' => 'single_elimination',
            'max_participants' => 2,
            'status' => 'registration',
            'entry_type' => 'solo',
            'entry_opens_at' => now()->subDay(),
            'entry_closes_at' => now()->addDay(),
        ], $overrides));
    }

    private function player(int $rating = 1000, string $role = 'player'): User
    {
        return User::factory()->create(['role' => $role, 'rating' => $rating]);
    }

    public function test_entries_are_confirmed_until_the_cap_then_waitlisted(): void
    {
        $t = $this->tournament(['max_participants' => 2]);

        $a = $this->service->enter($t, $this->player());
        $b = $this->service->enter($t, $this->player());
        $c = $this->service->enter($t, $this->player());

        $this->assertSame(Entry::CONFIRMED, $a->status);
        $this->assertSame(Entry::CONFIRMED, $b->status);
        $this->assertSame(Entry::WAITLISTED, $c->status);
        $this->assertSame(1, $c->waitlistPosition());
        $this->assertSame(2, $t->confirmedCount());
    }

    public function test_withdrawal_promotes_the_first_waitlisted_entrant(): void
    {
        $t = $this->tournament(['max_participants' => 1]);

        $first = $this->service->enter($t, $this->player());
        $second = $this->service->enter($t, $this->player());
        $third = $this->service->enter($t, $this->player());

        $this->service->withdraw($first);

        $this->assertSame(Entry::WITHDRAWN, $first->fresh()->status);
        $this->assertSame(Entry::CONFIRMED, $second->fresh()->status);
        $this->assertNotNull($second->fresh()->promoted_at);
        $this->assertSame(Entry::WAITLISTED, $third->fresh()->status);
        $this->assertSame(1, $third->fresh()->waitlistPosition());
    }

    public function test_promotion_is_idempotent(): void
    {
        $t = $this->tournament(['max_participants' => 1]);
        $this->service->enter($t, $this->player());
        $this->service->enter($t, $this->player());

        $this->assertSame(0, $this->service->promoteFromWaitlist($t));
        $this->assertSame(0, $this->service->promoteFromWaitlist($t));
        $this->assertSame(1, $t->confirmedCount());
    }

    public function test_raising_the_cap_lets_the_scheduler_promote_waiting_entrants(): void
    {
        $t = $this->tournament(['max_participants' => 1]);
        $this->service->enter($t, $this->player());
        $waiting = $this->service->enter($t, $this->player());

        $t->forceFill(['max_participants' => 2])->save();

        $this->assertSame(1, $this->service->promoteFromWaitlist($t));
        $this->assertSame(Entry::CONFIRMED, $waiting->fresh()->status);
    }

    public function test_rating_below_minimum_is_rejected(): void
    {
        $t = $this->tournament(['min_rating' => 1200]);

        $this->expectException(EntryException::class);
        $this->expectExceptionMessage('below the minimum');
        $this->service->enter($t, $this->player(1100));
    }

    public function test_rating_above_maximum_is_rejected(): void
    {
        $t = $this->tournament(['max_rating' => 1500]);

        $this->expectException(EntryException::class);
        $this->expectExceptionMessage('above the maximum');
        $this->service->enter($t, $this->player(1600));
    }

    public function test_rating_exactly_on_the_limit_is_accepted(): void
    {
        $t = $this->tournament(['min_rating' => 1200, 'max_rating' => 1500]);

        $this->assertSame(Entry::CONFIRMED, $this->service->enter($t, $this->player(1200))->status);
        $this->assertSame(Entry::CONFIRMED, $this->service->enter($t, $this->player(1500))->status);
    }

    public function test_entry_outside_the_window_is_rejected(): void
    {
        $closed = $this->tournament(['entry_closes_at' => now()->subHour()]);
        $notYetOpen = $this->tournament(['entry_opens_at' => now()->addHour()]);

        foreach ([$closed, $notYetOpen] as $t) {
            try {
                $this->service->enter($t, $this->player());
                $this->fail('Expected an EntryException');
            } catch (EntryException $e) {
                $this->assertStringContainsString('Entry is closed', $e->getMessage());
            }
        }
    }

    public function test_the_same_player_cannot_enter_twice(): void
    {
        $t = $this->tournament();
        $p = $this->player();
        $this->service->enter($t, $p);

        $this->expectException(EntryException::class);
        $this->service->enter($t, $p);
    }

    public function test_a_withdrawn_player_can_re_enter_and_goes_to_the_back_of_the_queue(): void
    {
        $t = $this->tournament(['max_participants' => 1]);
        $p = $this->player();
        $first = $this->service->enter($t, $p);
        $this->service->enter($t, $this->player());

        $this->service->withdraw($first);
        $again = $this->service->enter($t, $p);

        $this->assertSame($first->id, $again->id); // same row re-used
        $this->assertSame(Entry::WAITLISTED, $again->status);
        $this->assertSame(1, Entry::where('tournament_id', $t->id)->where('user_id', $p->id)->count());
    }

    public function test_a_waitlisted_entrant_who_became_ineligible_is_skipped(): void
    {
        $t = $this->tournament(['max_participants' => 1, 'min_rating' => 1000]);
        $a = $this->player(1000);
        $b = $this->player(1100);
        $c = $this->player(1200);

        $first = $this->service->enter($t, $a);
        $second = $this->service->enter($t, $b);
        $third = $this->service->enter($t, $c);

        $b->forceFill(['rating' => 900])->save(); // drops below the minimum while waiting

        $this->service->withdraw($first);

        $this->assertSame(Entry::WITHDRAWN, $second->fresh()->status);
        $this->assertStringContainsString('No longer eligible', $second->fresh()->status_reason);
        $this->assertSame(Entry::CONFIRMED, $third->fresh()->status);
    }

    public function test_no_promotion_or_withdrawal_after_the_bracket_is_generated(): void
    {
        $t = $this->tournament(['max_participants' => 1]);
        $a = $this->service->enter($t, $this->player());
        $b = $this->service->enter($t, $this->player());

        $t->forceFill(['status' => 'in_progress'])->save();

        $this->assertSame(0, $this->service->promoteFromWaitlist($t));
        $this->assertSame(Entry::WAITLISTED, $b->fresh()->status);

        $this->expectException(EntryException::class);
        $this->service->withdraw($a);
    }

    public function test_organizers_cannot_enter(): void
    {
        $this->expectException(EntryException::class);
        $this->service->enter($this->tournament(), $this->player(1000, 'organizer'));
    }

    // ---- team tournaments (assumes Team has captain_id and RosterSlot has team_id, player_id, is_substitute)

    private function team(User $captain, int $starters): Team
    {
        $team = Team::forceCreate(['name' => 'Team ' . $captain->id, 'captain_id' => $captain->id]);
        RosterSlot::forceCreate(['team_id' => $team->id, 'player_id' => $captain->id, 'is_substitute' => false]);
        for ($i = 1; $i < $starters; $i++) {
            RosterSlot::forceCreate(['team_id' => $team->id, 'player_id' => $this->player()->id, 'is_substitute' => false]);
        }
        return $team;
    }

    public function test_team_roster_must_match_the_required_size(): void
    {
        $t = $this->tournament(['entry_type' => 'team', 'team_size' => 3]);
        $captain = $this->player(1000, 'team_captain');
        $team = $this->team($captain, 2);

        $this->expectException(EntryException::class);
        $this->expectExceptionMessage('exactly 3 starting players');
        $this->service->enter($t, $captain, $team);
    }

    public function test_team_with_a_full_roster_is_accepted_and_only_the_captain_can_enter(): void
    {
        $t = $this->tournament(['entry_type' => 'team', 'team_size' => 3]);
        $captain = $this->player(1000, 'team_captain');
        $team = $this->team($captain, 3);

        $entry = $this->service->enter($t, $captain, $team);
        $this->assertSame(Entry::CONFIRMED, $entry->status);
        $this->assertNull($entry->user_id);
        $this->assertSame($team->id, $entry->team_id);

        $other = $this->tournament(['entry_type' => 'team', 'team_size' => 3]);
        $this->expectException(EntryException::class);
        $this->service->enter($other, $this->player(1000, 'team_captain'), $team);
    }

    public function test_team_is_rejected_when_a_starter_is_outside_the_rating_range(): void
    {
        $t = $this->tournament(['entry_type' => 'team', 'team_size' => 2, 'max_rating' => 1500]);
        $captain = $this->player(1000, 'team_captain');
        $team = $this->team($captain, 2);
        User::whereIn('id', RosterSlot::where('team_id', $team->id)->pluck('player_id'))
            ->where('id', '!=', $captain->id)->update(['rating' => 1800]);

        $this->expectException(EntryException::class);
        $this->expectExceptionMessage('above the maximum');
        $this->service->enter($t, $captain, $team);
    }
}
