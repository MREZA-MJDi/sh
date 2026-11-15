<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('seo_settings', 'footer_description')) {
                $table->text('footer_description')->nullable();
            }
            if (! Schema::hasColumn('seo_settings', 'footer_links')) {
                $table->json('footer_links')->nullable();
            }
            if (! Schema::hasColumn('seo_settings', 'trust_badge_path')) {
                $table->string('trust_badge_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_settings', function (Blueprint $table): void {
            foreach (['trust_badge_path', 'footer_links', 'footer_description'] as $column) {
                if (Schema::hasColumn('seo_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
