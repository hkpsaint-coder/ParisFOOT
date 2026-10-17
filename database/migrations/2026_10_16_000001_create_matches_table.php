<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('equipe_a');
            $table->string('equipe_b');
            $table->dateTime('date_match')->index();
            $table->string('competition')->index();
            $table->enum('source', ['api', 'manuel'])->default('manuel');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};