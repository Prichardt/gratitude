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
        Schema::create('benefit_gratitude_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gratitude_benefit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gratitude_level_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('value')->nullable();
            $table->string('value_type')->nullable(); // percentage or fixed amount   
            $table->json('calculation')->nullable(); // calculation rules for the benefit   
            $table->boolean('is_active')->default(true);
            $table->boolean('web_status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_gratitude_level');
    }
};
