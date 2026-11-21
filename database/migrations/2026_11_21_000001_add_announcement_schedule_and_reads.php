<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable()->after('published_at')->index();
            $table->timestamp('ends_at')->nullable()->after('starts_at')->index();
            $table->string('priority', 16)->default('normal')->after('ends_at')->index();
            $table->string('action_url', 2048)->nullable()->after('priority');
            $table->string('action_label', 80)->nullable()->after('action_url');
        });

        Schema::create('announcement_user_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['announcement_id', 'user_id'], 'announcement_user_reads_unique');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_user_reads');

        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn(['starts_at', 'ends_at', 'priority', 'action_url', 'action_label']);
        });
    }
};
