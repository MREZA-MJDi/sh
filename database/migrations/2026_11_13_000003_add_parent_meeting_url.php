<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parent_meetings', function (Blueprint $table): void {
            $table->text('meeting_url')->nullable()->after('meeting_type');
        });
    }

    public function down(): void
    {
        Schema::table('parent_meetings', function (Blueprint $table): void {
            $table->dropColumn('meeting_url');
        });
    }
};
