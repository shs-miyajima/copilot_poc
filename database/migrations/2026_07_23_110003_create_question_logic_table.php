<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_logic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('question_options')->cascadeOnDelete();
            $table->foreignId('target_question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('action')->default('jump'); // skip / jump
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_logic');
    }
};
