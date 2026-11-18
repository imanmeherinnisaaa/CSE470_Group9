<?php

namespace Tests\Unit;

use App\Services\TournamentConfigurationService;
use PHPUnit\Framework\TestCase;

class TournamentConfigurationTest extends TestCase
{
    private function base(array $override = []): array
    {
        return array_merge([
            'format' => 'single_elimination', 'min_participants' => 4, 'max_participants' => 16,
        ], $override);
    }

    public function test_valid_configuration_has_no_errors(): void
    {
        $this->assertSame([], (new TournamentConfigurationService())->validate($this->base()));
    }

    public function test_swiss_rounds_cannot_exceed_players_minus_one(): void
    {
        $errors = (new TournamentConfigurationService())->validate(
            $this->base(['format' => 'swiss', 'min_participants' => 4, 'swiss_rounds' => 4])
        );
        $this->assertArrayHasKey('swiss_rounds', $errors);
    }

    public function test_max_cannot_be_below_min(): void
    {
        $errors = (new TournamentConfigurationService())->validate(
            $this->base(['min_participants' => 8, 'max_participants' => 4])
        );
        $this->assertArrayHasKey('max_participants', $errors);
    }

    public function test_double_elimination_needs_four_players(): void
    {
        $errors = (new TournamentConfigurationService())->validate(
            $this->base(['format' => 'double_elimination', 'min_participants' => 2])
        );
        $this->assertArrayHasKey('min_participants', $errors);
    }

    public function test_schedule_window_must_be_ordered(): void
    {
        $errors = (new TournamentConfigurationService())->validate($this->base([
            'starts_at' => '2026-12-10 10:00', 'ends_at' => '2026-12-09 10:00',
        ]));
        $this->assertArrayHasKey('ends_at', $errors);
    }
}