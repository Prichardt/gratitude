<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gratitude_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level_name');
            $table->integer('min_points')->default(0);
            $table->integer('max_points')->nullable();
            $table->boolean('status')->default(true);
            $table->string('redeemation_points_per_dollar')->default(0);
            $table->text('stay_active_rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gratitude_levels');
    }
};
