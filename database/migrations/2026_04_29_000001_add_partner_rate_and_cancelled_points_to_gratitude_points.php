<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gratitude_levels', 'partner_points_per_dollar')) {
            Schema::table('gratitude_levels', function (Blueprint $table) {
                $table->decimal('partner_points_per_dollar', 8, 2)
                    ->default(35)
                    ->after('redemption_points_per_dollar');
            });
        }

        DB::table('gratitude_levels')
            ->where(function ($query) {
                $query->whereNull('partner_points_per_dollar')
                    ->orWhere('partner_points_per_dollar', 0);
            })
            ->update([
                'partner_points_per_dollar' => DB::raw('COALESCE(NULLIF(redemption_points_per_dollar, 0), 35)'),
            ]);

        if (! Schema::hasColumn('earned_points', 'cancelled_points')) {
            Schema::table('earned_points', function (Blueprint $table) {
                $table->integer('cancelled_points')
                    ->default(0)
                    ->after('redeemed_points');
            });
        }

        DB::table('earned_points')
            ->whereNotNull('cancel_id')
            ->where('cancelled_points', 0)
            ->update([
                'cancelled_points' => DB::raw('CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) ELSE 0 END'),
            ]);

        if (! Schema::hasColumn('bonus_points', 'cancelled_points')) {
            Schema::table('bonus_points', function (Blueprint $table) {
                $table->integer('cancelled_points')
                    ->default(0)
                    ->after('redeemed_points');
            });
        }

        DB::table('bonus_points')
            ->whereNotNull('cancel_id')
            ->where('cancelled_points', 0)
            ->update([
                'cancelled_points' => DB::raw('CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) ELSE 0 END'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('bonus_points', 'cancelled_points')) {
            Schema::table('bonus_points', function (Blueprint $table) {
                $table->dropColumn('cancelled_points');
            });
        }

        if (Schema::hasColumn('earned_points', 'cancelled_points')) {
            Schema::table('earned_points', function (Blueprint $table) {
                $table->dropColumn('cancelled_points');
            });
        }

        if (Schema::hasColumn('gratitude_levels', 'partner_points_per_dollar')) {
            Schema::table('gratitude_levels', function (Blueprint $table) {
                $table->dropColumn('partner_points_per_dollar');
            });
        }
    }
};
