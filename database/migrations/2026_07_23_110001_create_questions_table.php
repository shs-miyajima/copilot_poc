<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            // radio / checkbox / text / textarea / rating / number / date / dropdown
            $table->string('type');
            $table->text('label');
            $table->text('hint')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->tinyInteger('scale_min')->nullable();
            $table->tinyInteger('scale_max')->nullable();
            $table->timestamps();

            $table->index(['survey_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
