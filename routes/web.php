<?php
use App\Http\Controllers\SeedingController;
use App\Http\Controllers\SwissController;
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
   Route::resource('tournaments', TournamentController::class)->middleware('auth');
Route::middleware('auth')->group(function () {

    // F3 - Seeding Engine
    Route::get('/seeding-demo', [SeedingController::class, 'index'])
        ->name('seeding.demo');

    Route::post('/seeding-demo/generate', [SeedingController::class, 'generate'])
        ->name('seeding.demo.generate');

    // F5 - Swiss Pairing Engine
    Route::get('/swiss-demo', [SwissController::class, 'index'])
        ->name('swiss.demo');

    Route::post('/swiss-demo/generate', [SwissController::class, 'generate'])
        ->name('swiss.demo.generate');

    Route::post('/swiss-demo/scores', [SwissController::class, 'updateScores'])
        ->name('swiss.demo.scores');

    Route::post('/swiss-demo/reset', [SwissController::class, 'reset'])
        ->name('swiss.demo.reset');
});

   require __DIR__.'/auth.php';
