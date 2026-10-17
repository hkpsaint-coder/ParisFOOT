<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odds_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odd_id')->nullable()->constrained('odds')->nullOnDelete();
            $table->decimal('ancienne_cote_1', 8, 3)->nullable();
            $table->decimal('nouvelle_cote_1', 8, 3)->nullable();
            $table->decimal('ancienne_cote_X', 8, 3)->nullable();
            $table->decimal('nouvelle_cote_X', 8, 3)->nullable();
            $table->decimal('ancienne_cote_2', 8, 3)->nullable();
            $table->decimal('nouvelle_cote_2', 8, 3)->nullable();
            $table->foreignId('modifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('modifie_le')->useCurrent();
            $table->text('raison')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odds_history');
    }
};