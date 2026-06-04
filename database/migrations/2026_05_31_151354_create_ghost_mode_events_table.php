<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_mode_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('no_contact_protocol_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->string('trigger', 50)->nullable();
            $table->text('user_text')->nullable();
            $table->json('ai_result')->nullable();
            $table->timestamps();

            $table->index(['no_contact_protocol_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_mode_events');
    }
};
