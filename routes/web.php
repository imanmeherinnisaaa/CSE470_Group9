<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;
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
});
   Route::middleware(['auth', 'role:organizer'])->group(function () {
    Route::resource('tournaments', TournamentController::class)->except(['index', 'show']);
});

Route::middleware('auth')->group(function () {
    Route::resource('tournaments', TournamentController::class)->only(['index', 'show']);
});
require __DIR__.'/auth.php';

// ---- F2 Entry, Eligibility and Waitlist
Route::get('/tournaments/{tournament}/entry', [\App\Http\Controllers\EntryController::class, 'show'])->name('tournaments.entry.show');

Route::middleware('auth')->group(function () {
    Route::post('/tournaments/{tournament}/entry', [\App\Http\Controllers\EntryController::class, 'store'])->name('tournaments.entry.store');
    Route::delete('/entries/{entry}', [\App\Http\Controllers\EntryController::class, 'destroy'])->name('entries.destroy');
});

// ---- F13 Roster and Substitution Rules
Route::get('/teams/{team}/roster', [\App\Http\Controllers\RosterController::class, 'show'])->name('teams.roster.show');

Route::middleware('auth')->group(function () {
    Route::get('/my-team', [\App\Http\Controllers\RosterController::class, 'mine'])->name('teams.mine');
    Route::get('/teams/create', [\App\Http\Controllers\RosterController::class, 'create'])->name('teams.create');
    Route::post('/teams', [\App\Http\Controllers\RosterController::class, 'store'])->name('teams.store');
    Route::patch('/teams/{team}', [\App\Http\Controllers\RosterController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [\App\Http\Controllers\RosterController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/roster', [\App\Http\Controllers\RosterController::class, 'addPlayer'])->name('teams.roster.add');
    Route::delete('/teams/{team}/roster/{slot}', [\App\Http\Controllers\RosterController::class, 'removePlayer'])->name('teams.roster.remove');
    Route::post('/teams/{team}/roster/substitute', [\App\Http\Controllers\RosterController::class, 'substitute'])->name('teams.roster.substitute');
});

