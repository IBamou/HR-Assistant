<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('candidates', 'user_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });

            if (DB::table('users')->exists()) {
                $firstUserId = DB::table('users')->orderBy('id')->value('id');
                DB::table('candidates')->whereNull('user_id')->update(['user_id' => $firstUserId]);
            }

            Schema::table('candidates', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index('user_id');
            });
        }

        if (! Schema::hasColumn('documents', 'user_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });

            if (DB::table('users')->exists()) {
                $firstUserId = DB::table('users')->orderBy('id')->value('id');
                DB::table('documents')->whereNull('user_id')->update(['user_id' => $firstUserId]);
            }

            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index('user_id');
            });
        }

        if (! Schema::hasColumn('applications', 'user_id')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });

            if (DB::table('users')->exists()) {
                $firstUserId = DB::table('users')->orderBy('id')->value('id');
                DB::table('applications')->whereNull('user_id')->update(['user_id' => $firstUserId]);
            }

            Schema::table('applications', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('analyses')) {
            Schema::create('analyses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->smallInteger('matching_score')->nullable();
                $table->string('recommendation')->nullable();
                $table->json('extracted_skills')->nullable();
                $table->json('missing_skills')->nullable();
                $table->text('strengths')->nullable();
                $table->text('gaps')->nullable();
                $table->text('justification')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();

                $table->index('user_id');
                $table->index('application_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');

        $tables = ['candidates', 'documents', 'applications'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('user_id');
                });
            }
        }
    }
};
