<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gratitude_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('benefit_key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratitude_benefits');
    }
};
