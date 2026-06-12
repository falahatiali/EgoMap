<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_user_check_ins')) {
            return;
        }

        Schema::create('aether_user_check_ins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aether_generated_program_id')
                ->nullable()
                ->constrained('aether_generated_programs', indexName: 'auci_program_fk')
                ->nullOnDelete();
            $table->date('check_in_date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('body_fat_percent', 4, 1)->nullable();
            $table->unsignedTinyInteger('sleep_quality')->nullable();
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->unsignedTinyInteger('stress_level')->nullable();
            $table->unsignedTinyInteger('energy_level')->nullable();
            $table->unsignedTinyInteger('hunger_level')->nullable();
            $table->unsignedTinyInteger('soreness_level')->nullable();
            $table->unsignedTinyInteger('workout_adherence_percent')->nullable();
            $table->unsignedTinyInteger('nutrition_adherence_percent')->nullable();
            $table->text('feedback')->nullable();
            $table->json('pain_points')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'check_in_date'], 'auci_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_user_check_ins');
    }
};
