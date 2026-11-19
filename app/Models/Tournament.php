<?php

namespace App\Models;

use App\Models\Concerns\HasEntryRules;
use App\Services\TournamentConfigurationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasEntryRules;

    protected $fillable = [
        'name', 'game', 'format', 'min_participants', 'max_participants', 'swiss_rounds',
        'best_of', 'rules', 'registration_opens_at', 'registration_closes_at',
        'starts_at', 'ends_at', 'status', 'organizer_id', 'bracket_generated_at',
        'entry_opens_at', 'entry_closes_at', 'min_rating', 'max_rating',
        'entry_type', 'team_size', 'roster_lock_at',
    ];

    protected $casts = [
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'bracket_generated_at' => 'datetime',
        'entry_opens_at' => 'datetime',
        'entry_closes_at' => 'datetime',
        'roster_lock_at' => 'datetime',
        'min_rating' => 'integer',
        'max_rating' => 'integer',
        'team_size' => 'integer',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function isElimination(): bool
    {
        return in_array($this->format, ['single_elimination', 'double_elimination'], true);
    }

    /** Settings lock once a bracket exists. */
    public function isLocked(): bool
    {
        return $this->matches()->exists();
    }

    public function formatLabel(): string
    {
        return TournamentConfigurationService::FORMATS[$this->format] ?? $this->format;
    }
}