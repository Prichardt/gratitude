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
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('old_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->bigInteger('journey_id')->nullable();
            $table->date('date')->nullable();
            $table->string('category')->nullable();
            $table->string('gratitudeNumber')->nullable();
            $table->integer('points')->default(0);
            $table->string('amount')->nullable();
            $table->string('description')->nullable();
            $table->json('points_breakdown')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
