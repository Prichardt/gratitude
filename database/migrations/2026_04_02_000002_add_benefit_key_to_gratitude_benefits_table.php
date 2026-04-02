<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gratitude_benefits', function (Blueprint $table) {
            // Programmatic slug used to gate features by level (e.g. 'journey_payment', 'experience_payment')
            $table->string('benefit_key')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('gratitude_benefits', function (Blueprint $table) {
            $table->dropColumn('benefit_key');
        });
    }
};
