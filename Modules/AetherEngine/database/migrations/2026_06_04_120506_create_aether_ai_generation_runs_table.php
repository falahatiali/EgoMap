<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_ai_generation_runs')) {
            return;
        }

        Schema::create('aether_ai_generation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('generation_type', 32);
            $table->foreignId('aether_prompt_template_id')
                ->nullable()
                ->constrained('aether_prompt_templates', indexName: 'aagr_prompt_fk')
                ->nullOnDelete();
            $table->foreignId('aether_generated_program_id')
                ->nullable()
                ->constrained('aether_generated_programs', indexName: 'aagr_program_fk')
                ->nullOnDelete();
            $table->string('model', 64)->nullable();
            $table->string('provider', 32)->nullable();
            $table->json('input_payload');
            $table->json('output_payload')->nullable();
            $table->string('status', 24)->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('cost_cents')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'generation_type'], 'aagr_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_ai_generation_runs');
    }
};
