<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('bookmaker');
            $table->decimal('cote_1', 8, 3);
            $table->decimal('cote_X', 8, 3);
            $table->decimal('cote_2', 8, 3);
            $table->enum('source', ['api', 'manuel'])->default('manuel');
            $table->boolean('is_modified')->default(false);
            $table->timestamps();
            $table->unique(['match_id', 'bookmaker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};