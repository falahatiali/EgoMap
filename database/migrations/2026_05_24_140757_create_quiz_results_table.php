<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('outcome_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->json('dimension_scores')->nullable();
            $table->json('free_report')->nullable();
            $table->json('premium_report')->nullable();
            $table->string('status')->default('pending');
            $table->string('ai_model')->nullable();
            $table->string('ai_prompt_version')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
