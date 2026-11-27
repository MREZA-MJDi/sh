<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_banners', function (Blueprint $table): void {
            $table->text('subtitle')->nullable()->after('alt_text');
            $table->string('badge', 120)->nullable()->after('subtitle');
            $table->string('button_text', 100)->nullable()->after('target_url');
            $table->string('secondary_button_text', 100)->nullable()->after('button_text');
            $table->string('secondary_button_url', 2048)->nullable()->after('secondary_button_text');
            $table->string('bg_color', 120)->nullable()->after('image_position');
            $table->string('text_color', 50)->nullable()->after('bg_color');
            $table->string('layout_type', 30)->default('side_image')->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('seo_banners', function (Blueprint $table): void {
            $table->dropColumn([
                'subtitle',
                'badge',
                'button_text',
                'secondary_button_text',
                'secondary_button_url',
                'bg_color',
                'text_color',
                'layout_type',
            ]);
        });
    }
};
