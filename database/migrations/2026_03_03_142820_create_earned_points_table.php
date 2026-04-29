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
        $remainingPointsExpression = 'CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END';

        Schema::create('earned_points', function (Blueprint $table) use ($remainingPointsExpression) {
            $table->id();
            $table->bigInteger('old_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->bigInteger('journey_id')->nullable();
            $table->string('gratitudeNumber');
            $table->integer('points')->default(0);
            $table->json('points_breakdown')->nullable(); // {"points": 100, "rate": 0.5, "amount": 200, "entry_date": "2026-12-31", "usable_date": "2026-12-31", "journey_id": 123, "journey_number": "J123", "journey_type": "curated", "journey_name": "Testing Name", "journey_end_date": "2026-12-31", "journey_start_date": "2026-12-31", "expires_at": "2026-12-31"}
            $table->integer('redeemed_points')->default(0);
            $table->integer('cancelled_points')->default(0);
            $table->integer('remaining_points')->virtualAs($remainingPointsExpression);
            $table->text('redemption_history')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('date')->nullable();
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->bigInteger('cancel_id')->nullable();
            $table->string('status')->nullable()->default('pending'); // 'pending', 'active', 'expired'
            $table->timestamp('usable_date')->nullable(); // The journey return date
            $table->timestamp('expires_at')->nullable(); // usable_date + 2 years
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earned_points');
    }
};
