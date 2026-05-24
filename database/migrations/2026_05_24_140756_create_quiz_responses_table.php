<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            $table->unique(['quiz_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_responses');
    }
};
