<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('spotplayer_course_id')->nullable()->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('external_student_id')->nullable()->unique();
        });

        Schema::table('spot_player_settings', function (Blueprint $table) {
            $table->string('mode', 20)->default('test');
        });

        Schema::table('spot_player_licenses', function (Blueprint $table) {
            $table->text('license_key')->nullable();
            $table->text('download_url')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('quiz24_settings', function (Blueprint $table) {
            $table->string('api_token_header', 100)->nullable();
            $table->string('api_token_prefix', 100)->nullable();
            $table->string('exams_endpoint', 500)->nullable();
            $table->string('exams_collection_path', 190)->nullable();
            $table->string('exam_id_path', 190)->nullable();
            $table->string('exam_title_path', 190)->nullable();
            $table->string('results_endpoint', 500)->nullable();
            $table->string('results_collection_path', 190)->nullable();
            $table->string('result_student_identifier_path', 190)->nullable();
            $table->string('result_display_name_path', 190)->nullable();
            $table->string('result_score_path', 190)->nullable();
            $table->string('result_max_score_path', 190)->nullable();
        });

        Schema::create('integration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->index();
            $table->string('action', 80)->index();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('status', 20)->default('running')->index();
            $table->json('request_meta')->nullable();
            $table->json('response_meta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz24_unmatched_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('external_identifier', 190);
            $table->string('display_name', 190)->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('max_score', 7, 2)->default(20);
            $table->string('status', 20)->default('unmatched')->index();
            $table->foreignId('resolved_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('source_data')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'external_identifier'], 'quiz24_unmatched_exam_identifier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz24_unmatched_participants');

        Schema::dropIfExists('integration_runs');

        Schema::table('quiz24_settings', function (Blueprint $table) {
            $table->dropColumn([
                'api_token_header',
                'api_token_prefix',
                'exams_endpoint',
                'exams_collection_path',
                'exam_id_path',
                'exam_title_path',
                'results_endpoint',
                'results_collection_path',
                'result_student_identifier_path',
                'result_display_name_path',
                'result_score_path',
                'result_max_score_path',
            ]);
        });

        Schema::table('spot_player_licenses', function (Blueprint $table) {
            $table->dropColumn(['license_key', 'download_url', 'error_message']);
        });

        Schema::table('spot_player_settings', function (Blueprint $table) {
            $table->dropColumn('mode');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['external_student_id']);
            $table->dropColumn('external_student_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['spotplayer_course_id']);
            $table->dropColumn('spotplayer_course_id');
        });
    }
};
