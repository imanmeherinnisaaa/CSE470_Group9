<?php

namespace Tests\Unit;

use App\Services\SwissPairingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SwissPairingServiceTest extends TestCase
{
    public function test_generates_pairs_without_rematches_when_possible(): void
    {
        $players = [
            ['id' => 1, 'score' => 3, 'opponents' => [2]],
            ['id' => 2, 'score' => 3, 'opponents' => [1]],
            ['id' => 3, 'score' => 2, 'opponents' => []],
            ['id' => 4, 'score' => 2, 'opponents' => []],
        ];

        $result = (new SwissPairingService())->generatePairings($players);

        self::assertCount(2, $result['matches']);
        self::assertNull($result['bye']);

        foreach ($result['matches'] as $match) {
            self::assertFalse($match['rematch']);
        }
    }

    public function test_odd_field_assigns_bye_to_lowest_eligible_player(): void
    {
        $players = [
            ['id' => 1, 'score' => 5, 'has_received_bye' => false],
            ['id' => 2, 'score' => 3, 'has_received_bye' => false],
            ['id' => 3, 'score' => 1, 'has_received_bye' => false],
        ];

        $result = (new SwissPairingService())->generatePairings($players);

        self::assertSame(3, $result['bye']['id']);
        self::assertTrue($result['bye']['has_received_bye']);
        self::assertCount(1, $result['matches']);
    }

    public function test_bye_is_not_repeated_when_another_player_is_eligible(): void
    {
        $players = [
            ['id' => 1, 'score' => 5, 'has_received_bye' => false],
            ['id' => 2, 'score' => 3, 'has_received_bye' => false],
            ['id' => 3, 'score' => 1, 'has_received_bye' => true],
        ];

        $result = (new SwissPairingService())->generatePairings($players);

        self::assertSame(2, $result['bye']['id']);
    }

    public function test_rejects_duplicate_participant_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SwissPairingService())->generatePairings([
            ['id' => 1, 'score' => 2],
            ['id' => 1, 'score' => 1],
        ]);
    }

    public function test_rejects_a_bye_when_everyone_already_received_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SwissPairingService())->generatePairings([
            ['id' => 1, 'score' => 2, 'has_received_bye' => true],
            ['id' => 2, 'score' => 1, 'has_received_bye' => true],
            ['id' => 3, 'score' => 0, 'has_received_bye' => true],
        ]);
    }
}