<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtue_routines', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtue_habit_id')->constrained()->cascadeOnDelete();
            $table->text('personal_note')->nullable();
            $table->string('goal_type')->default('days_count');
            $table->unsignedSmallInteger('goal_target')->default(21);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('best_streak')->default(0);
            $table->unsignedSmallInteger('total_successes')->default(0);
            $table->unsignedSmallInteger('total_slips')->default(0);
            $table->string('status')->default('active');
            $table->date('last_success_date')->nullable();
            $table->date('last_slip_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtue_routines');
    }
};
