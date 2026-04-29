<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $remainingPointsExpression = 'CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->fixPointTable('earned_points');
        $this->fixPointTable('bonus_points');
    }

    public function down(): void
    {
        //
    }

    private function fixPointTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'cancelled_points')) {
            return;
        }

        if (Schema::hasColumn($table, 'remaining_points')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `remaining_points`");
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `cancelled_points` INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE `{$table}` ADD `remaining_points` INT GENERATED ALWAYS AS ({$this->remainingPointsExpression}) VIRTUAL");
    }
};
