<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gratitude_earned_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('gratitudeNumber')->index();
            $table->foreignId('benefit_id')->nullable()->constrained('gratitude_benefits')->nullOnDelete();
            $table->bigInteger('user_id')->nullable()->index();
            $table->bigInteger('journey_id')->nullable()->index();
            $table->string('benefit_name');
            $table->string('benefit_key')->nullable()->index();
            $table->text('description');
            $table->string('benefit_value')->nullable();
            $table->string('value_type')->nullable();
            $table->json('project_data')->nullable();
            $table->date('date');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratitude_earned_benefits');
    }
};
