<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_banners') && ! Schema::hasColumn('seo_banners', 'image_position')) {
            Schema::table('seo_banners', function (Blueprint $table): void {
                $table->string('image_position', 15)->default('50% 50%');
            });
        }

        if (Schema::hasTable('seo_articles') && ! Schema::hasColumn('seo_articles', 'image_position')) {
            Schema::table('seo_articles', function (Blueprint $table): void {
                $table->string('image_position', 15)->default('50% 50%');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_articles') && Schema::hasColumn('seo_articles', 'image_position')) {
            Schema::table('seo_articles', fn (Blueprint $table) => $table->dropColumn('image_position'));
        }

        if (Schema::hasTable('seo_banners') && Schema::hasColumn('seo_banners', 'image_position')) {
            Schema::table('seo_banners', fn (Blueprint $table) => $table->dropColumn('image_position'));
        }
    }
};
