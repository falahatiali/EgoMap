<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('relationship_duration', 30)->nullable()->after('breakup_duration');
            $table->string('breakup_initiator', 20)->nullable()->after('relationship_duration');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'relationship_duration',
                'breakup_initiator',
            ]);
        });
    }
};
