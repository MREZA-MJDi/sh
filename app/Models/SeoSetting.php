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
            'panels_eyebrow' => 'پنل‌های اختصاصی',
            'panels_title' => 'هر نقش، یک پنل مخصوص خودش',
            'panels_description' => 'پس از ورود، دسترسی تو بر اساس نقش حساب مشخص می‌شود.',
            'panel_cards' => [
                ['title' => 'پنل دانش‌آموز', 'body' => 'دوره‌ها، تکالیف، آزمون‌های آنلاین و پیگیری رشد تحصیلی.'],
                ['title' => 'پنل مدرس', 'body' => 'مدیریت دوره‌ها، ثبت نمرات، پاسخ به سؤالات و بررسی تکالیف.'],
                ['title' => 'پنل والدین', 'body' => 'گزارش پیشرفت درسی، ثبت حضور و غیاب و وضعیت تحصیلی فرزند.'],
                ['title' => 'پنل مدیریت', 'body' => 'نظارت بر کاربران، انتشار دوره‌ها، گزارش‌های مالی و پشتیبانی.'],
            ],
            'benefits_eyebrow' => 'چرا شیخان؟',
            'benefits_title' => 'آموزشی که تا رسیدن به نتیجه کنارت می‌ماند',
            'benefits_description' => 'در آموزشگاه شیخان فقط ویدیو نمی‌بینی؛ تمرین می‌کنی، بازخورد می‌گیری و رشدت را قدم‌به‌قدم می‌بینی.',
            'benefits_primary_label' => 'مشاهده دوره‌ها',
            'benefits_secondary_label' => 'مشاوره رایگان',
            'benefits' => [
                ['title' => 'مدرسان منتخب', 'body' => 'مدرس‌های باسابقه و بررسی‌شده برای هر درس و پایه.'],
                ['title' => 'دسترسی همیشگی', 'body' => 'جلسه‌ها را هر زمان و هر چندبار که خواستی آنلاین ببین.'],
                ['title' => 'پیگیری رشد تحصیلی', 'body' => 'نمودار پیشرفت و آزمون‌ها در پنل اختصاصی دانشجو.'],
                ['title' => 'گزارش برای والدین', 'body' => 'پیشرفت، آزمون‌ها و حضور فرزند در پنل والدین.'],
                ['title' => 'آزمون و تمرین', 'body' => 'تمرین منظم با سنجش دقیق و بازخورد مستقیم مدرس.'],
                ['title' => 'پشتیبانی سریع', 'body' => 'پاسخ سؤال‌های درسی‌ات در سریع‌ترین زمان ممکن.'],
            ],
            'testimonials_eyebrow' => 'نظرهای ثبت‌شده',
            'testimonials_title' => 'تجربه دانش‌آموزان',
            'teachers_eyebrow' => 'مدرسان برتر',
            'teachers_title' => 'با مدرس‌های شیخان آشنا شو',
            'teachers_description' => 'یادگیری از اساتید باسابقه و متخصص در هر رشته',
            'articles_eyebrow' => 'از مجله شیخان',
            'articles_title' => 'تازه‌ترین مقاله‌ها',
            'articles_description' => 'مطالب آموزشی و نکته‌های کاربردی آکادمی.',
            'blog_page_title' => 'مجله شیخان',
            'blog_page_description' => 'نکته‌های مطالعه، مسیر تحصیلی و آموزش‌های تخصصی آکادمی.',
            'courses_page_title' => 'دوره‌های آموزشی',
            'courses_page_description' => 'بر اساس پایه تحصیلی و درس، دوره مناسب خودت را انتخاب کن.',
            'teachers_page_title' => 'مدرسان شیخان',
            'teachers_page_description' => 'مدرس‌های فعال و دوره‌های منتشرشده آن‌ها را ببین.',
            'teachers_cta_title' => 'مدرس هستی؟ با ما همکاری کن',
            'teachers_cta_body' => 'برای آشنایی با امکانات حساب مدرس، وارد حساب کاربری شو.',
            'teachers_cta_label' => 'ورود به حساب',
            'contact_page_title' => 'تماس با ما',
            'contact_page_description' => 'پیامت را ثبت کن تا تیم شیخان آن را بررسی کند.',
            'contact_support_title' => 'پشتیبانی',
            'contact_support_body' => 'پیام‌ها در سامانه ثبت می‌شوند و تیم پشتیبانی آن‌ها را بررسی می‌کند.',
            'contact_faq_title' => 'سؤالات متداول',
            'contact_topics' => ['پشتیبانی دوره', 'مسائل مالی', 'همکاری', 'سایر'],
            'contact_faq_items' => [
                ['question' => 'چطور سرفصل دوره را ببینم؟', 'answer' => 'وارد صفحه جزئیات دوره شو؛ فصل‌ها و درس‌های منتشرشده آنجا نمایش داده می‌شوند.'],
                ['question' => 'چطور با مدرس‌ها آشنا شوم؟', 'answer' => 'صفحه مدرسان، اطلاعات مدرس‌های فعال را نمایش می‌دهد.'],
            ],
            'cart_page_title' => 'سبد خرید',
            'cart_page_description' => 'دوره‌هایی که برای بررسی انتخاب کرده‌ای.',
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

        $homeSections = array_replace_recursive(
            static::defaultHomeSections(),
            is_array($settings->home_sections) ? $settings->home_sections : []
        );
        if ($homeSections !== $settings->home_sections) {
            $settings->forceFill(['home_sections' => $homeSections])->saveQuietly();
        }

        return $settings;
    }
}
