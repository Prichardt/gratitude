<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gratitude_levels', function (Blueprint $table) {
            // How many years a member has to accumulate enough points to keep their level
            $table->unsignedSmallInteger('level_interval_years')->default(2)->after('bonus_expire_days');

            // Jetsetter-specific retention: minimum qualifying journeys within the interval
            $table->unsignedTinyInteger('jetsetter_min_journeys')->nullable()->after('level_interval_years');

            // Jetsetter-specific retention: minimum journey duration (days) to count as qualifying
            $table->unsignedTinyInteger('jetsetter_min_journey_days')->nullable()->after('jetsetter_min_journeys');

            // General program terms & conditions shown alongside this level's benefits grid
            $table->text('terms_conditions')->nullable()->after('level_rules');

            // Terms & conditions specific to this membership level
            $table->text('level_terms_conditions')->nullable()->after('terms_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('gratitude_levels', function (Blueprint $table) {
            $table->dropColumn([
                'level_interval_years',
                'jetsetter_min_journeys',
                'jetsetter_min_journey_days',
                'terms_conditions',
                'level_terms_conditions',
            ]);
        });
    }
};
