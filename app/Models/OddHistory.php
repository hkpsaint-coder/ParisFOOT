<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OddHistory extends Model
{
    protected $table = 'odds_history';

    protected $fillable = ['odd_id', 'ancienne_cote_1', 'nouvelle_cote_1', 'ancienne_cote_X', 'nouvelle_cote_X', 'ancienne_cote_2', 'nouvelle_cote_2', 'modifie_par', 'modifie_le', 'raison'];

    protected function casts(): array
    {
        return ['modifie_le' => 'datetime'];
    }

    public function odd(): BelongsTo
    {
        return $this->belongsTo(Odd::class, 'odd_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }
}