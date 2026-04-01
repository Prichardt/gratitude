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
        Schema::create('gratitudes', function (Blueprint $table) {
            $table->id();            
            $table->bigInteger('old_id')->nullable();
            $table->string('gratitudeNumber')->nullable()->unique();
            $table->integer('totalPoints')->nullable();
            $table->integer('totalEarnedPoints')->nullable();
            $table->integer('totalBonusPoints')->nullable();
            $table->integer('totalExpiredPoints')->nullable();
            $table->integer('totalCancelledPoints')->nullable();
            $table->integer('totalRedeemedPoints')->nullable();
            $table->integer('totalRemainingPoints')->nullable();
            $table->integer('useablePoints')->nullable();
            $table->integer('nonUseablePoints')->nullable();
            $table->string('level')->nullable(); // Explorer, Globetrotter, Jetsetter
            $table->json('levelHistory')->nullable(); // [{"level":"Explorer","startDate":"2024-01-01","endDate":"2024-02-01"},{"level":"Globetrotter","startDate":"2024-02-02","endDate":null}]
            $table->string('status')->nullable();
            $table->string('statusChange')->nullable(); // upgrade, downgrade
            $table->string('statusChangeReason')->nullable();
            $table->boolean('systemLevelUpdate')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('importStatus')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gratitudes');
    }
};
