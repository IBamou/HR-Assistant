<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->unique(['email', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['email', 'user_id']);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
