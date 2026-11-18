<?php

namespace Tests\Unit;

use App\Services\SeedingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SeedingServiceTest extends TestCase
{
    private function participants(): array
    {
        return [
            ['id' => 1, 'name' => 'A', 'rating_snapshot' => 2100, 'team_id' => 10],
            ['id' => 2, 'name' => 'B', 'rating_snapshot' => 1900, 'team_id' => 10],
            ['id' => 3, 'name' => 'C', 'rating_snapshot' => 1700, 'team_id' => 20],
            ['id' => 4, 'name' => 'D', 'rating_snapshot' => 1500, 'team_id' => 20],
            ['id' => 5, 'name' => 'E', 'rating_snapshot' => 1400, 'team_id' => null],
        ];
    }

    public function test_rating_seeding_supports_rating_snapshot(): void
    {
        $result = (new SeedingService())->seedByRating($this->participants());

        self::assertSame([1, 2, 3, 4, 5], array_column($result, 'id'));
        self::assertSame([1, 2, 3, 4, 5], array_column($result, 'seed'));
    }
    public function test_random_seeding_includes_every_participant_once(): void
   {
       $participants = $this->participants();

       $result = (new SeedingService())->seedRandomly($participants);

       $resultIds = array_column($result, 'id');
       $resultSeeds = array_column($result, 'seed');

       sort($resultIds);

        self::assertSame([1, 2, 3, 4, 5], $resultIds);
        self::assertSame([1, 2, 3, 4, 5], $resultSeeds);
        self::assertCount(5, $result);
    }
    public function test_manual_seeding_respects_order(): void
    {
        $result = (new SeedingService())->seedManually(
            $this->participants(),
            [3, 1, 5, 2, 4]
        );

        self::assertSame([3, 1, 5, 2, 4], array_column($result, 'id'));
    }

    public function test_manual_seeding_rejects_duplicate_or_missing_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SeedingService())->seedManually($this->participants(), [1, 1, 2, 3, 4]);
    }

    public function test_teammate_group_mapping_distributes_group_members(): void
    {
        $participants = $this->participants();
        $groups = [1 => 100, 2 => 100, 3 => 200, 4 => 200, 5 => 300];

        $result = (new SeedingService())->seedByRating($participants, true, $groups);
        $positions = [];

        foreach ($result as $participant) {
            $positions[$participant['id']] = $participant['seed'];
        }

        self::assertNotSame(1, abs($positions[1] - $positions[2]));
        self::assertNotSame(1, abs($positions[3] - $positions[4]));
    }
}
