<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('respondent_token', 64)->nullable();
            $table->string('ip_address', 64)->nullable(); // SHA-256ハッシュ化済み
            $table->string('cookie_token', 64)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // パフォーマンス用インデックス
            $table->index('survey_id');
            $table->index('cookie_token');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};
