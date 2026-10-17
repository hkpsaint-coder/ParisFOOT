<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchGame extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = ['equipe_a', 'equipe_b', 'date_match', 'competition', 'source', 'created_by', 'external_id'];

    protected function casts(): array
    {
        return ['date_match' => 'datetime'];
    }

    public function odds(): HasMany
    {
        return $this->hasMany(Odd::class, 'match_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class, 'match_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}