<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $fillable = ['user_id', 'action', 'table_cible', 'enregistrement_id', 'anciennes_valeurs', 'nouvelles_valeurs', 'raison', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['anciennes_valeurs' => 'array', 'nouvelles_valeurs' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}