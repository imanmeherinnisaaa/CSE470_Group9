<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Entry;
use App\Models\GameMatch;
use App\Models\Station;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchedulingService
{
    public function assignMatch(GameMatch $match, TimeSlot $timeSlot): GameMatch
    {
        return DB::transaction(function () use ($match, $timeSlot) {

            $match = GameMatch::whereKey($match->id)
                ->lockForUpdate()
                ->firstOrFail();

            $timeSlot = TimeSlot::whereKey($timeSlot->id)
                ->lockForUpdate()
                ->firstOrFail();

            $station = Station::whereKey($timeSlot->station_id)
                ->lockForUpdate()
                ->first();

            if (!$station || !$station->is_active) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'This time slot has no active station.',
                ]);
            }

            if (
                $match->tournament_id !== $timeSlot->tournament_id ||
                $station->tournament_id !== $match->tournament_id
            ) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'The match, time slot, and station must belong to the same tournament.',
                ]);
            }

            if ($timeSlot->end_time <= $timeSlot->start_time) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'The time slot end time must be after its start time.',
                ]);
            }

            if ($match->is_bye || !$match->entry1_id || !$match->entry2_id) {
                throw ValidationException::withMessages([
                    'match' => 'A bye or incomplete match cannot be scheduled.',
                ]);
            }

            $entries = Entry::whereIn('id', [
                $match->entry1_id,
                $match->entry2_id,
            ])
                ->where('tournament_id', $match->tournament_id)
                ->where('status', Entry::CONFIRMED)
                ->with('team')
                ->get();

            if ($entries->count() !== 2) {
                throw ValidationException::withMessages([
                    'match' => 'Both match entries must be confirmed.',
                ]);
            }

            $playerIds = [];

            foreach ($entries as $entry) {
                $playerIds = array_merge(
                    $playerIds,
                    $this->participantIdsForEntry($entry)
                );
            }

            $playerIds = array_values(array_unique($playerIds));

            if (empty($playerIds)) {
                throw ValidationException::withMessages([
                    'match' => 'No participating players were found.',
                ]);
            }

            $availablePlayerIds = Availability::whereIn('user_id', $playerIds)
                ->where('tournament_id', $match->tournament_id)
                ->whereDate('available_date', $timeSlot->slot_date)
                ->where('start_time', '<=', $timeSlot->start_time)
                ->where('end_time', '>=', $timeSlot->end_time)
                ->pluck('user_id')
                ->unique()
                ->all();

            $unavailablePlayers = array_diff(
                $playerIds,
                $availablePlayerIds
            );

            if (!empty($unavailablePlayers)) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'One or more players are not available during this time slot.',
                ]);
            }

            $stationOccupied = TimeSlot::where('station_id', $station->id)
                ->whereDate('slot_date', $timeSlot->slot_date)
                ->where('start_time', '<', $timeSlot->end_time)
                ->where('end_time', '>', $timeSlot->start_time)
                ->whereHas('gameMatches', function ($query) use ($match) {
                    $query->where('game_matches.id', '<>', $match->id);
                })
                ->exists();

            if ($stationOccupied) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'This station is already booked during an overlapping time.',
                ]);
            }

            $overlappingMatches = GameMatch::where('id', '<>', $match->id)
                ->whereNotNull('time_slot_id')
                ->whereHas('timeSlot', function ($query) use ($timeSlot) {
                    $query->whereDate('slot_date', $timeSlot->slot_date)
                        ->where('start_time', '<', $timeSlot->end_time)
                        ->where('end_time', '>', $timeSlot->start_time);
                })
                ->with(['entry1.team', 'entry2.team'])
                ->get();

            foreach ($overlappingMatches as $otherMatch) {
                $otherPlayerIds = array_merge(
                    $otherMatch->entry1
                        ? $this->participantIdsForEntry($otherMatch->entry1)
                        : [],
                    $otherMatch->entry2
                        ? $this->participantIdsForEntry($otherMatch->entry2)
                        : []
                );

                if (array_intersect($playerIds, $otherPlayerIds)) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'One or more players are already scheduled for another match during this time.',
                    ]);
                }
            }

            $match->update([
                'time_slot_id' => $timeSlot->id,
            ]);

            return $match->fresh([
                'timeSlot.station',
                'entry1',
                'entry2',
            ]);
        });
    }

    private function participantIdsForEntry(Entry $entry): array
    {
        if ($entry->user_id) {
            return [$entry->user_id];
        }

        if ($entry->team_id) {
            if (!$entry->team) {
                return [];
            }

            return $entry->team
                ->starters()
                ->pluck('player_id')
                ->all();
        }

        return [];
    }
}
