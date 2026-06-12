<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aether_program_edit_events')) {
            return;
        }

        Schema::create('aether_program_edit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aether_generated_program_id')
                ->constrained('aether_generated_programs', indexName: 'apee_program_fk')
                ->cascadeOnDelete();
            $table->string('editable_type');
            $table->unsignedBigInteger('editable_id');
            $table->string('action', 32);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('source', 24)->default('user');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['editable_type', 'editable_id'], 'apee_editable_idx');
            $table->index(['user_id', 'aether_generated_program_id'], 'apee_user_prog_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aether_program_edit_events');
    }
};
