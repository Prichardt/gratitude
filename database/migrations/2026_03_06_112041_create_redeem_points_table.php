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
        Schema::create('redeem_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedBigInteger('journey_id')->nullable();
            $table->unsignedBigInteger('cancel_id')->nullable();
            $table->string('gratitudeNumber');
            $table->decimal('amount', 10, 2)->nullable();
            $table->bigInteger('points')->nullable();
            $table->string('roomStatus')->nullable();
            $table->string('status')->nullable();
            $table->string('category')->nullable();
            $table->text('reason')->nullable();
            $table->json('points_breakdown')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redeem_points');
    }
};
