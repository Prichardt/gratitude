<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gratitude_levels', function (Blueprint $table) {
            $table->unsignedInteger('earned_expire_days')->default(730)->after('redeemation_points_per_dollar');
            $table->unsignedInteger('bonus_expire_days')->default(730)->after('earned_expire_days');
        });

        DB::table('gratitude_levels')
            ->whereNull('earned_expire_days')
            ->orWhereNull('bonus_expire_days')
            ->update([
                'earned_expire_days' => 730,
                'bonus_expire_days' => 730,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gratitude_levels', function (Blueprint $table) {
            $table->dropColumn(['earned_expire_days', 'bonus_expire_days']);
        });
    }
};
