<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('learning_resources', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_resources', 'release_at')) {
                $table->timestamp('release_at')->nullable()->after('visibility');
            }
            if (! Schema::hasColumn('learning_resources', 'downloadable')) {
                $table->boolean('downloadable')->default(true)->after('release_at');
            }
            if (! Schema::hasColumn('learning_resources', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            }
        });

        Schema::create('learning_resource_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_resource_id')->constrained('learning_resources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();
            $table->index(['learning_resource_id', 'accessed_at'], 'resource_access_resource_date_idx');
            $table->index(['user_id', 'accessed_at'], 'resource_access_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_resource_access_logs');

        Schema::table('learning_resources', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['release_at', 'downloadable', 'sort_order'],
                fn (string $column): bool => Schema::hasColumn('learning_resources', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};