<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odd extends Model
{
    use HasFactory;

    protected $fillable = ['match_id', 'bookmaker', 'cote_1', 'cote_X', 'cote_2', 'source', 'is_modified'];

    protected function casts(): array
    {
        return ['cote_1' => 'float', 'cote_X' => 'float', 'cote_2' => 'float', 'is_modified' => 'boolean'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class, 'odd_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OddHistory::class, 'odd_id');
    }
}