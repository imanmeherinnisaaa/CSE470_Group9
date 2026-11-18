<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\SeedingController;
use App\Http\Controllers\SwissController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResultController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/matches/{gameMatch}/result', [ResultController::class, 'create'])
        ->name('matches.result.create');

    Route::post('/matches/{gameMatch}/result', [ResultController::class, 'store'])
        ->name('matches.result.store');
});

// F1 Tournament Format Configuration + F4 Bracket Generation

Route::middleware(['auth', 'role:organizer'])->group(function () {
    Route::resource('tournaments', TournamentController::class)
        ->except(['index', 'show']);

    Route::post('/tournaments/{tournament}/bracket', [BracketController::class, 'generate'])
        ->name('bracket.generate');

    // F6 Match Scheduling
    Route::get('/tournaments/{tournament}/schedule', [\App\Http\Controllers\ScheduleController::class, 'index'])
        ->name('schedule.index');

    Route::post('/tournaments/{tournament}/schedule/assign', [\App\Http\Controllers\ScheduleController::class, 'assign'])
        ->name('schedule.assign');

    Route::post('/tournaments/{tournament}/schedule/stations', [\App\Http\Controllers\ScheduleController::class, 'storeStation'])
        ->name('schedule.stations.store');

    Route::post('/tournaments/{tournament}/schedule/time-slots', [\App\Http\Controllers\ScheduleController::class, 'storeTimeSlot'])
        ->name('schedule.time-slots.store');
});

Route::middleware('auth')->group(function () {
    Route::resource('tournaments', TournamentController::class)
        ->only(['index', 'show']);

    Route::get('/tournaments/{tournament}/bracket', [BracketController::class, 'show'])
        ->name('bracket.show');
});

// F2 Entry, Eligibility and Waitlist

Route::get('/tournaments/{tournament}/entry', [\App\Http\Controllers\EntryController::class, 'show'])
    ->name('tournaments.entry.show');

Route::middleware('auth')->group(function () {
    Route::post('/tournaments/{tournament}/entry', [\App\Http\Controllers\EntryController::class, 'store'])
        ->name('tournaments.entry.store');

    Route::delete('/entries/{entry}', [\App\Http\Controllers\EntryController::class, 'destroy'])
        ->name('entries.destroy');
});

// F13 Roster and Substitution Rules

Route::get('/teams/{team}/roster', [\App\Http\Controllers\RosterController::class, 'show'])
    ->name('teams.roster.show');

Route::middleware('auth')->group(function () {
    Route::get('/my-team', [\App\Http\Controllers\RosterController::class, 'mine'])
        ->name('teams.mine');

    Route::get('/teams/create', [\App\Http\Controllers\RosterController::class, 'create'])
        ->name('teams.create');

    Route::post('/teams', [\App\Http\Controllers\RosterController::class, 'store'])
        ->name('teams.store');

    Route::patch('/teams/{team}', [\App\Http\Controllers\RosterController::class, 'update'])
        ->name('teams.update');

    Route::delete('/teams/{team}', [\App\Http\Controllers\RosterController::class, 'destroy'])
        ->name('teams.destroy');

    Route::post('/teams/{team}/roster', [\App\Http\Controllers\RosterController::class, 'addPlayer'])
        ->name('teams.roster.add');

    Route::delete('/teams/{team}/roster/{slot}', [\App\Http\Controllers\RosterController::class, 'removePlayer'])
        ->name('teams.roster.remove');

    Route::post('/teams/{team}/roster/substitute', [\App\Http\Controllers\RosterController::class, 'substitute'])
        ->name('teams.roster.substitute');
});

// F3 - Seeding Engine and F5 - Swiss Pairing Engine

Route::middleware('auth')->group(function () {
    Route::get('/seeding-demo', [SeedingController::class, 'index'])
        ->name('seeding.demo');

    Route::post('/seeding-demo/generate', [SeedingController::class, 'generate'])
        ->name('seeding.demo.generate');

    Route::get('/swiss-demo', [SwissController::class, 'index'])
        ->name('swiss.demo');

    Route::post('/swiss-demo/generate', [SwissController::class, 'generate'])
        ->name('swiss.demo.generate');

    Route::post('/swiss-demo/scores', [SwissController::class, 'updateScores'])
        ->name('swiss.demo.scores');

    Route::post('/swiss-demo/reset', [SwissController::class, 'reset'])
        ->name('swiss.demo.reset');
});

// F6 - Player Availability

Route::middleware('auth')->group(function () {
    Route::get('/tournaments/{tournament}/availability', [\App\Http\Controllers\AvailabilityController::class, 'index'])
        ->name('availability.index');

    Route::post('/tournaments/{tournament}/availability', [\App\Http\Controllers\AvailabilityController::class, 'store'])
        ->name('availability.store');
});

require __DIR__.'/auth.php';
