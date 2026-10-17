<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('odd_id')->constrained('odds')->cascadeOnDelete();
            $table->decimal('proba_1', 7, 3);
            $table->decimal('proba_X', 7, 3);
            $table->decimal('proba_2', 7, 3);
            $table->decimal('proba_echec_1', 7, 3);
            $table->decimal('proba_echec_X', 7, 3);
            $table->decimal('proba_echec_2', 7, 3);
            $table->decimal('marge_bookmaker', 7, 3);
            $table->boolean('value_bet_detecte')->default(false);
            $table->json('probabilites_estimees')->nullable();
            $table->timestamps();
            $table->index(['match_id', 'odd_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};