<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\Tournament;
use App\Services\EntryService;
use Illuminate\Console\Command;

class PromoteWaitlist extends Command
{
    protected $signature = 'entries:promote-waitlist';

    protected $description = 'Move waitlisted entrants into free tournament slots (safe to run repeatedly)';

    public function handle(EntryService $entries): int
    {
        $total = 0;

        Tournament::query()
            ->where('status', 'registration')
            ->whereHas('entries', fn ($q) => $q->where('status', Entry::WAITLISTED))
            ->each(function (Tournament $tournament) use ($entries, &$total) {
                $total += $entries->promoteFromWaitlist($tournament);
            });

        $this->info("Promoted {$total} entrant(s) from waitlists.");

        return self::SUCCESS;
    }
}
