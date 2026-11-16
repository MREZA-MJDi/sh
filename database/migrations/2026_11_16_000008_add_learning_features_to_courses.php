<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'learning_features')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->json('learning_features')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'learning_features')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->dropColumn('learning_features');
            });
        }
    }
};
