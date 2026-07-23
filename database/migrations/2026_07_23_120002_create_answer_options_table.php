<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('question_options')->cascadeOnDelete();
            $table->timestamps();

            $table->index('answer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_options');
    }
};
