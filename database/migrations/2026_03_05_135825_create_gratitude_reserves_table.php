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
        Schema::create('gratitude_reserves', function (Blueprint $table) {
            $table->id();
            $table->string('journey_id')->nullable();
            $table->json('reserved_breakdown_data')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->dateTime('date')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->default('journey'); //stock gains etc
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gratitude_reserves');
    }
};
