<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    protected $fillable = ['match_id', 'odd_id', 'proba_1', 'proba_X', 'proba_2', 'proba_echec_1', 'proba_echec_X', 'proba_echec_2', 'marge_bookmaker', 'value_bet_detecte', 'probabilites_estimees'];

    protected function casts(): array
    {
        return ['value_bet_detecte' => 'boolean', 'probabilites_estimees' => 'array'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function odd(): BelongsTo
    {
        return $this->belongsTo(Odd::class, 'odd_id');
    }
}