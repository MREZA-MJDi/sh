<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            if (! Schema::hasColumn('seo_settings', 'home_sections')) {
                $table->json('home_sections')->nullable();
            }
        });
        if (Schema::hasTable('seo_settings')) {
            DB::table('seo_settings')->whereNull('footer_links')->update([
                'footer_description' => 'آکادمی شیخان؛ آموزش هدفمند و همراهی مستمر در مسیر یادگیری.',
                'footer_links' => json_encode([
                    ['label' => 'دوره‌ها', 'url' => '/courses'],
                    ['label' => 'مدرسان', 'url' => '/teachers'],
                    ['label' => 'مقالات', 'url' => '/articles'],
                    ['label' => 'تماس با ما', 'url' => '/contact'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            DB::table('seo_settings')->whereNull('home_sections')->update([
                'home_sections' => json_encode([ 'hero_badge' => 'ویژه دانش‌آموزان شیخان' ], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('seo_settings', function (Blueprint $table): void {
            foreach (['home_sections', 'trust_badge_path', 'footer_links', 'footer_description'] as $column) {
                if (Schema::hasColumn('seo_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
