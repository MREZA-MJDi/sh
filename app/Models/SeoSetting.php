<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = [
        'id', 'site_name', 'default_title', 'title_separator', 'default_description',
        'default_keywords', 'canonical_base_url', 'default_og_image_path',
        'organization_name', 'organization_url', 'organization_logo_path',
        'twitter_handle', 'google_site_verification', 'bing_site_verification',
        'footer_description', 'footer_links', 'trust_badge_path',
        'robots_index', 'robots_follow', 'sitemap_enabled', 'social_links', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'default_keywords' => 'array',
            'social_links' => 'array',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'sitemap_enabled' => 'boolean',
            'footer_links' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'آکادمی شیخان',
                'default_title' => 'آکادمی شیخان',
                'title_separator' => ' | ',
                'default_description' => 'آکادمی آموزشی شیخان؛ همراه مسیر یادگیری و پیشرفت دانش‌آموزان.',
                'default_keywords' => ['آکادمی شیخان', 'آموزش', 'آموزش آنلاین'],
                'robots_index' => true,
                'robots_follow' => true,
                'sitemap_enabled' => true,
                'footer_description' => 'آکادمی شیخان؛ آموزش هدفمند و همراهی مستمر در مسیر یادگیری.',
                'footer_links' => [
                    ['label' => 'دوره‌ها', 'url' => '/courses'],
                    ['label' => 'مدرسان', 'url' => '/teachers'],
                    ['label' => 'مقالات', 'url' => '/articles'],
                    ['label' => 'تماس با ما', 'url' => '/contact'],
                ],
            ]
        );
    }
}
