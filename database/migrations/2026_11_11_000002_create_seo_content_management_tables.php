<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('site_name', 160)->default('آکادمی شیخان');
            $table->string('default_title', 180)->default('آکادمی شیخان');
            $table->string('title_separator', 10)->default(' | ');
            $table->text('default_description')->nullable();
            $table->json('default_keywords')->nullable();
            $table->string('canonical_base_url', 2048)->nullable();
            $table->string('default_og_image_path')->nullable();
            $table->string('organization_name', 180)->nullable();
            $table->string('organization_url', 2048)->nullable();
            $table->string('organization_logo_path')->nullable();
            $table->string('twitter_handle', 80)->nullable();
            $table->string('google_site_verification', 255)->nullable();
            $table->string('bing_site_verification', 255)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->boolean('sitemap_enabled')->default(true);
            $table->json('social_links')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seo_banners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('alt_text', 180);
            $table->string('image_path');
            $table->string('placement', 40)->default('articles_top')->index();
            $table->string('target_url', 2048)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('seo_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 190)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('featured_image_path')->nullable();
            $table->string('image_alt', 180)->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('og_image_path')->nullable();
            $table->json('keywords')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_articles');
        Schema::dropIfExists('seo_banners');
        Schema::dropIfExists('seo_settings');
    }
};
