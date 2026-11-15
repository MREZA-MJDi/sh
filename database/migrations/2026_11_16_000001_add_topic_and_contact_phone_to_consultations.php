<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            if (! Schema::hasColumn('consultations', 'topic')) {
                $table->string('topic', 190)->nullable()->after('type');
            }
            if (! Schema::hasColumn('consultations', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            if (Schema::hasColumn('consultations', 'contact_phone')) {
                $table->dropColumn('contact_phone');
            }
            if (Schema::hasColumn('consultations', 'topic')) {
                $table->dropColumn('topic');
            }
        });
    }
};
