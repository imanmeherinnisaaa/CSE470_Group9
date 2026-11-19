<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts and two tournaments that are already open for entry, for trying F2 and F13.
 * Run:  php artisan db:seed --class=DemoSeeder      (safe to run again)
 * Every account has the password: password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $make = function (string $name, string $email, string $role, int $rating): User {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'rating' => $rating,
                'email_verified_at' => now(),
            ])->save();

            return $user;
        };

        $organizer = $make('Olivia Organizer', 'organizer@example.com', 'organizer', 1000);
        $make('Casey Captain', 'captain@example.com', 'player', 1000);

        foreach ([900, 1000, 1100, 1200, 1300, 1400] as $i => $rating) {
            $n = $i + 1;
            $make("Player {$n}", "player{$n}@example.com", 'player', $rating);
        }

        // Solo tournament: 2 spots and a rating range, so the cap, waitlist and rating rules are easy to see.
        Tournament::updateOrCreate(['name' => 'Solo Cup'], [
            'game' => 'Chess',
            'format' => 'single_elimination',
            'min_participants' => 2,
            'max_participants' => 2,
            'status' => 'registration',
            'organizer_id' => $organizer->id,
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(7),
            'entry_type' => 'solo',
            'min_rating' => 1000,
            'max_rating' => 1500,
        ]);

        // Team tournament: every team needs exactly 2 starters.
        Tournament::updateOrCreate(['name' => 'Team Cup'], [
            'game' => 'Valorant',
            'format' => 'single_elimination',
            'min_participants' => 2,
            'max_participants' => 4,
            'status' => 'registration',
            'organizer_id' => $organizer->id,
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(7),
            'entry_type' => 'team',
            'team_size' => 2,
        ]);
    }
}
