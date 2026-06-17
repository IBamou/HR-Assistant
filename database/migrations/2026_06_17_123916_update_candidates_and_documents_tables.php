<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('address')->nullable()->after('phone');
            $table->text('summary')->nullable()->after('address');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('chunk_count');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['address', 'summary']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
