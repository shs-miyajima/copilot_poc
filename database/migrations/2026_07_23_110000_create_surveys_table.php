<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft / published / closed
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_responses')->nullable();
            $table->string('response_limit_type')->default('none'); // none / cookie / ip
            $table->text('thanks_message')->nullable();
            $table->string('public_token', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            // パフォーマンス用インデックス
            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
