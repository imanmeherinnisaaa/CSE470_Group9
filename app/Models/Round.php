<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = ['tournament_id', 'number', 'name', 'bracket'];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class)->orderBy('position');
    }
}