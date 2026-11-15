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
        'footer_description', 'footer_links', 'trust_badge_path', 'home_sections',
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
            'home_sections' => 'array',
        ];
    }

    public static function defaultHomeSections(): array
    {
        return [
            'hero_badge' => 'ویژه دانش‌آموزان شیخان',
            'hero_description' => 'آموزش هدفمند و مرحله‌به‌مرحله با اساتید مجرب و دسترسی همیشگی به محتوای منتشرشده.',
            'hero_primary_label' => 'مشاهده اطلاعات',
            'hero_secondary_label' => 'درخواست راهنمایی',
            'categories_eyebrow' => 'دسته‌بندی‌ها',
            'categories_title' => 'موضوع مورد علاقه‌ات را انتخاب کن',
            'stats_labels' => ['ثبت‌نام فعال', 'درس منتشرشده', 'مدرس متخصص', 'دوره منتشرشده'],
            'latest_courses_eyebrow' => 'جدیدترین‌ها',
            'latest_courses_title' => 'دوره‌های تازه',
            'latest_courses_description' => 'تازه‌ترین آموزش‌های منتشر شده در آکادمی شیخان',
            'popular_courses_eyebrow' => 'انتخاب دانشجوها',
            'popular_courses_title' => 'پرطرفدارترین دوره‌ها',
            'popular_courses_description' => 'محبوب‌ترین دوره‌ها بر اساس استقبال و ثبت‌نام دانش‌آموزان',
            'steps_eyebrow' => 'مسیر یادگیری',
            'steps_title' => 'چهار قدم تا تسلط',
            'steps_description' => 'فرآیند اصولی یادگیری در آکادمی شیخان',
            'steps' => [
                ['title' => 'انتخاب مسیر', 'body' => 'هدفت را مشخص کن تا دوره و برنامه مطالعاتی متناسب را پیدا کنی.'],
                ['title' => 'آموزش مرحله‌ای', 'body' => 'درس‌ها را با ویدیوها و سرفصل‌های منظم دنبال کن.'],
                ['title' => 'تمرین و آزمون', 'body' => 'با تمرین‌های مستمر و آزمون‌ها آموخته‌هایت را بسنج.'],
                ['title' => 'پیگیری و پشتیبانی', 'body' => 'با مدرس در ارتباط باش و مسیر پیشرفتت را پیگیری کن.'],
            ],
            'panels_title' => 'هر نقش، یک پنل مخصوص خودش',
            'panels_description' => 'پس از ورود، دسترسی تو بر اساس نقش حساب مشخص می‌شود.',
            'testimonials_eyebrow' => 'نظرهای ثبت‌شده',
            'testimonials_title' => 'تجربه دانش‌آموزان',
            'teachers_eyebrow' => 'مدرسان برتر',
            'teachers_title' => 'با مدرس‌های شیخان آشنا شو',
            'teachers_description' => 'یادگیری از اساتید باسابقه و متخصص در هر رشته',
            'articles_eyebrow' => 'از مجله شیخان',
            'articles_title' => 'تازه‌ترین مقاله‌ها',
            'articles_description' => 'مطالب آموزشی و نکته‌های کاربردی آکادمی.',
            'cta_title' => 'اولین جلسه را رایگان ببین',
            'cta_body' => 'بدون نیاز به ثبت کارت بانکی و بدون تعهد، تدریس اساتید شیخان را بررسی کن.',
            'cta_label' => 'شروع یادگیری',
            'faq_eyebrow' => 'سؤالات متداول',
            'faq_title' => 'قبل از شروع، این‌ها را بدان',
            'faq_description' => 'جواب سؤالت اینجا نبود؟ تیم پشتیبانی شیخان برای راهنمایی در کنارت است.',
            'faq_cta_label' => 'تماس با پشتیبانی',
            'faq_items' => [
                ['question' => 'چطور در دوره‌ها ثبت‌نام کنم؟', 'answer' => 'با حساب کاربری وارد شو، دوره مدنظرت را انتخاب و مراحل ثبت‌نام را تکمیل کن.'],
                ['question' => 'تا کی به ویدیوها و محتوای دوره دسترسی دارم؟', 'answer' => 'مدت دسترسی هر دوره در صفحه همان دوره مشخص می‌شود.'],
                ['question' => 'اگر در مورد درس‌ها سؤال داشتم چطور بپرسم؟', 'answer' => 'از طریق پنل کاربری و ابزار پیام‌رسانی دوره با مدرس در ارتباط باش.'],
                ['question' => 'والدین چطور پیشرفت فرزندشان را پیگیری می‌کنند؟', 'answer' => 'در پنل اولیا گزارش آموزشی فرزند متصل‌شده قابل مشاهده است.'],
                ['question' => 'آیا آزمون‌ها دارای کارنامه هستند؟', 'answer' => 'نتایج آزمون‌های منتشرشده و قابل مشاهده در پنل دانش‌آموز نمایش داده می‌شوند.'],
            ],
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->firstOrCreate(
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
                'home_sections' => static::defaultHomeSections(),
            ]
        );

        if (! is_array($settings->home_sections) || $settings->home_sections === []) {
            $settings->forceFill(['home_sections' => static::defaultHomeSections()])->save();
        } else {
            $settings->forceFill([
                'home_sections' => array_replace_recursive(static::defaultHomeSections(), $settings->home_sections),
            ])->saveQuietly();
        }

        return $settings;
    }
}
