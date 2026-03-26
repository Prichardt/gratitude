<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bonus_points', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('old_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->bigInteger('journey_id')->nullable();
            $table->date('date')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('gratitudeNumber');
            $table->integer('points')->default(0);
            $table->json('points_breakdown')->nullable(); //
            $table->integer('redeemed_points')->default(0);
            $table->integer('remaining_points')->virtualAs('points - redeemed_points');
            $table->text('redemption_history')->nullable();
            $table->string('amount')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('cancel_id')->nullable();
            $table->boolean('status')->nullable()->default(true); // active, expired
            $table->softDeletes();
            $table->timestamps();


            $table->timestamp('usable_date')
                ->nullable()
                ->virtualAs('COALESCE(TIMESTAMP(`date`), `created_at`)');

            $table->timestamp('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_points');
    }
};
