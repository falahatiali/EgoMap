<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('type')->default('likert')->after('quiz_id');
            $table->foreignId('quiz_dimension_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->json('config')->nullable()->after('help_text');
            $table->dropColumn('dimension');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('dimension')->nullable();
            $table->dropConstrainedForeignId('quiz_dimension_id');
            $table->dropColumn(['type', 'config']);
        });
    }
};
