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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->string('title');
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->json('required_skills');
            $table->json('soft_skills')->nullable();
            $table->string('min_experience_level')->nullable();
            $table->string('education_level')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('location')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
