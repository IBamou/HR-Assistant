<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('candidates', 'extracted_text')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->longText('extracted_text')->nullable()->after('summary');
            });
        }

        if (Schema::hasTable('document_chunks')) {
            Schema::dropIfExists('document_chunks');
        }

        if (Schema::hasColumn('documents', 'chunk_count')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('chunk_count');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'chunk_count')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->integer('chunk_count')->default(0)->after('original_path');
            });
        }

        if (! Schema::hasTable('document_chunks')) {
            Schema::create('document_chunks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->integer('chunk_index');
                $table->longText('content');
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('candidates', 'extracted_text')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('extracted_text');
            });
        }
    }
};
