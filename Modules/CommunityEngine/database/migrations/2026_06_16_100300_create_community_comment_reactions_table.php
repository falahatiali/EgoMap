<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('community_comment_reactions')) {
            return;
        }

        Schema::create('community_comment_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained('community_comments')->cascadeOnDelete();
            $table->string('reaction_type', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'comment_id']);
            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comment_reactions');
    }
};
