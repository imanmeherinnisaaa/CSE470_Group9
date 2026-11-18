<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\RosterService;
use Illuminate\Console\Command;

class SyncRosterLocks extends Command
{
    protected $signature = 'rosters:sync-locks';

    protected $description = 'Update each roster slot\'s locked flag to match its team\'s lock state (safe to run repeatedly)';

    public function handle(RosterService $rosters): int
    {
        $count = 0;
        Team::query()->each(function (Team $team) use ($rosters, &$count) {
            $rosters->syncLocks($team);
            $count++;
        });

        $this->info("Checked {$count} team(s).");

        return self::SUCCESS;
    }
}
