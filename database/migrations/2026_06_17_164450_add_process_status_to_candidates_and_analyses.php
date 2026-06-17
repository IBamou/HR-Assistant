<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('candidates', 'process_status')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('process_status')->default('pending')->after('extraction_payload');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('candidates', 'process_status')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('process_status');
            });
        }
    }
};
