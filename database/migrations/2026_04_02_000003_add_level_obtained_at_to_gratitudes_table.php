<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gratitudes', function (Blueprint $table) {
            // Timestamp of when the member reached their current level; the retention interval counts from here
            $table->timestamp('level_obtained_at')->nullable()->after('levelHistory');
        });
    }

    public function down(): void
    {
        Schema::table('gratitudes', function (Blueprint $table) {
            $table->dropColumn('level_obtained_at');
        });
    }
};
