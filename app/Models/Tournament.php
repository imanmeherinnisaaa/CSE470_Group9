<?php

namespace App\Models;

use App\Services\TournamentConfigurationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    protected $fillable = [
        'name', 'game', 'format', 'min_participants', 'max_participants', 'swiss_rounds',
        'best_of', 'rules', 'registration_opens_at', 'registration_closes_at',
        'starts_at', 'ends_at', 'status', 'organizer_id', 'bracket_generated_at',
    ];

    protected $casts = [
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'bracket_generated_at' => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function isElimination(): bool
    {
        return in_array($this->format, ['single_elimination', 'double_elimination'], true);
    }

    /** Settings lock once a bracket exists. For now nothing locks; F4 will update this. */
    public function isLocked(): bool
    {
        return false;
    }

    public function formatLabel(): string
    {
        return TournamentConfigurationService::FORMATS[$this->format] ?? $this->format;
    }
}