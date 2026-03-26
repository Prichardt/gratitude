<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earned_points', function (Blueprint $table) {
            $table->boolean('expires_at_manual')->default(false)->after('expires_at');
        });

        Schema::table('bonus_points', function (Blueprint $table) {
            $table->boolean('expires_at_manual')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('earned_points', function (Blueprint $table) {
            $table->dropColumn('expires_at_manual');
        });

        Schema::table('bonus_points', function (Blueprint $table) {
            $table->dropColumn('expires_at_manual');
        });
    }
};
