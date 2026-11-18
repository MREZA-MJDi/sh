<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use App\Services\AuthenticationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SeoContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        return $admin;
    }

    private function seoUser(): User
    {
        $seo = User::factory()->create();
        $seo->roles()->attach(Role::query()->where('name', 'seo')->value('id'));

        return $seo;
    }

    private function teacher(): User
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        return $teacher;
    }

    public function test_seo_user_can_manage_seo_and_admin_panel_hides_content_optimization_from_admin(): void
    {
        $seo = $this->seoUser();
        $admin = $this->admin();
        $teacher = $this->teacher();

        // SEO user can access SEO dashboard and see SEO management
        $this->actingAs($seo)
            ->get(route('admin.seo.index'))
            ->assertOk()
            ->assertSee('مدیریت سئو')
            ->assertSee('مرکز مدیریت سئو')
            ->assertSee('پیش‌نمایش نتیجه جست‌وجو')
            ->assertSee('کد Google Search Console')
            ->assertSee('کنترل خزنده‌ها و نقشه سایت')
            ->assertSee('محتوا و بهینه‌سازی');

        $this->actingAs($seo)->get(route('admin.seo.articles.index'))
            ->assertOk()->assertSee('ایجاد مقاله جدید')->assertSee('تصویر Open Graph');

        $this->actingAs($seo)->get(route('admin.seo.banners.index'))
            ->assertOk()->assertSee('ثبت بنر جدید')->assertSee('متن جایگزین تصویر');

        // SEO user is redirected to SEO dashboard upon login
        $this->assertSame('admin.seo.index', app(AuthenticationService::class)->dashboardRouteName($seo));

        // Admin cannot see Content & Optimization section in admin panel
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('محتوا و بهینه‌سازی')
            ->assertDontSee(route('admin.seo.index'));

        // Admin cannot access SEO routes directly (403 Forbidden)
        $this->actingAs($admin)->get(route('admin.seo.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.seo.articles.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.seo.banners.index'))->assertForbidden();

        // Teacher cannot access SEO routes (403 Forbidden)
        $this->actingAs($teacher)->get(route('admin.seo.index'))->assertForbidden();
    }

    public function test_admin_cannot_change_seo_access_or_manage_seo_settings(): void
    {
        $admin = $this->admin();
        $seo = $this->seoUser();

        // Admin does not have permission to manage SEO
        $this->assertFalse($admin->hasPermissionTo('seo.manage'));
        $this->assertTrue($seo->hasPermissionTo('seo.manage'));

        // Admin cannot perform update settings POST/PATCH
        $this->actingAs($admin)
            ->patch(route('admin.seo.settings.update'), [
                'site_name' => 'تغییر غیرمجاز',
            ])
            ->assertForbidden();

        // Admin cannot create user with SEO role via UserManagementService
        $userManagement = app(UserManagementService::class);
        $this->expectException(ValidationException::class);
        $userManagement->create([
            'name' => 'سئو جدید',
            'email' => 'new-seo@example.com',
            'password' => 'SecurePass1234!',
        ], 'seo', $admin);
    }

    public function test_admin_cannot_modify_seo_user_status(): void
    {
        $admin = $this->admin();
        $seo = $this->seoUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $seo), ['is_active' => false])
            ->assertForbidden();
    }

    public function test_seo_user_can_update_seo_settings_and_robots_endpoint_uses_saved_values(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->patch(route('admin.seo.settings.update'), [
            'site_name' => 'آکادمی شیخان',
            'default_title' => 'آموزش و یادگیری',
            'title_separator' => ' | ',
            'default_description' => 'توضیح واقعی وب‌سایت برای موتورهای جست‌وجو',
            'default_keywords' => 'آموزش, شیخان, یادگیری',
            'canonical_base_url' => 'https://sheykhan.example',
            'organization_name' => 'آکادمی شیخان',
            'organization_url' => 'https://sheykhan.example',
            'twitter_handle' => '@sheykhan',
            'google_site_verification' => 'google-token',
            'bing_site_verification' => 'bing-token',
            'default_og_image' => UploadedFile::fake()->image('default-og.jpg', 1200, 630),
            'organization_logo' => UploadedFile::fake()->image('organization.png', 500, 500),
            'social_links' => ['instagram' => 'https://instagram.com/sheykhan', 'telegram' => 'https://t.me/sheykhan'],
            'robots_index' => '0',
            'robots_follow' => '0',
            'sitemap_enabled' => '0',
        ])->assertRedirect();

        $settings = SeoSetting::query()->firstOrFail();
        $this->assertSame(['آموزش', 'شیخان', 'یادگیری'], $settings->default_keywords);
        $this->assertFalse($settings->robots_index);
        $this->assertFalse($settings->robots_follow);
        $this->assertFalse($settings->sitemap_enabled);
        $this->assertSame($seo->id, $settings->updated_by);
        Storage::disk('public')->assertExists($settings->default_og_image_path);
        Storage::disk('public')->assertExists($settings->organization_logo_path);

        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /admin')
            ->assertDontSee('Sitemap:');

        $this->get(route('seo.articles.index'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('name="keywords" content="آموزش, شیخان, یادگیری"', false)
            ->assertSee('name="google-site-verification" content="google-token"', false)
            ->assertSee('name="msvalidate.01" content="bing-token"', false)
            ->assertSee('توضیح واقعی وب‌سایت برای موتورهای جست‌وجو')
            ->assertSee('property="og:site_name"', false)
            ->assertSee('name="twitter:site" content="@sheykhan"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:image"', false)
            ->assertSee('rel="canonical" href="https://sheykhan.example/articles"', false)
            ->assertSee('https://instagram.com/sheykhan', false);

        $this->get(route('seo.sitemap'))->assertNotFound();
    }

    public function test_seo_user_can_create_update_and_delete_banner_with_its_image(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->post(route('admin.seo.banners.store'), [
            'title' => 'بنر ثبت‌نام',
            'alt_text' => 'ثبت‌نام دوره‌های آموزشی',
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
            'placement' => 'articles_top',
            'target_url' => '/articles',
            'open_in_new_tab' => '1',
            'sort_order' => 2,
            'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'status' => 'active',
        ])->assertRedirect();

        $banner = SeoBanner::query()->firstOrFail();
        $oldPath = $banner->image_path;
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame($seo->id, $banner->created_by);
        $this->assertTrue($banner->open_in_new_tab);

        $this->actingAs($seo)->patch(route('admin.seo.banners.update', $banner), [
            'title' => 'بنر به‌روزشده',
            'alt_text' => 'تصویر جدید ثبت‌نام',
            'image' => UploadedFile::fake()->image('updated.webp', 1000, 350),
            'placement' => 'sitewide',
            'target_url' => 'https://sheykhan.example/register',
            'sort_order' => 1,
            'status' => 'inactive',
        ])->assertRedirect();

        $banner->refresh();
        $this->assertSame('بنر به‌روزشده', $banner->title);
        $this->assertSame('sitewide', $banner->placement);
        $this->assertSame('inactive', $banner->status);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($banner->image_path);

        $this->actingAs($seo)->delete(route('admin.seo.banners.destroy', $banner))->assertRedirect();
        $this->assertDatabaseMissing('seo_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($banner->image_path);
    }

    public function test_seo_user_can_manage_articles_and_only_published_articles_are_public(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->post(route('admin.seo.articles.store'), [
            'title' => 'راهنمای یادگیری مؤثر',
            'slug' => 'effective-learning-guide',
            'excerpt' => 'چند راهکار برای یادگیری بهتر',
            'body' => '<p>متن آموزشی <strong>مهم</strong></p><script>alert(1)</script>',
            'meta_title' => 'راهنمای یادگیری | شیخان',
            'meta_description' => 'راهنمای کامل یادگیری مؤثر برای دانش‌آموزان',
            'keywords' => 'یادگیری, مطالعه, آموزش',
            'canonical_url' => 'https://sheykhan.example/articles/effective-learning-guide',
            'featured_image' => UploadedFile::fake()->image('article-cover.jpg', 1200, 800),
            'image_alt' => 'تصویر راهنمای یادگیری',
            'og_image' => UploadedFile::fake()->image('article-og.jpg', 1200, 630),
            'status' => 'published',
        ])->assertRedirect();

        $article = SeoArticle::query()->where('slug', 'effective-learning-guide')->firstOrFail();
        $this->assertSame($seo->id, $article->author_id);
        $this->assertSame(['یادگیری', 'مطالعه', 'آموزش'], $article->keywords);
        $this->assertNotNull($article->published_at);
        Storage::disk('public')->assertExists($article->featured_image_path);
        Storage::disk('public')->assertExists($article->og_image_path);
        $featuredImagePath = $article->featured_image_path;
        $ogImagePath = $article->og_image_path;
        $this->assertStringContainsString('<strong>مهم</strong>', $article->body);
        $this->assertStringNotContainsString('<script', $article->body);

        $this->actingAs($seo)->post(route('admin.seo.articles.store'), [
            'title' => 'مقاله پیش‌نویس',
            'slug' => 'draft-article',
            'body' => '<p>این متن هنوز عمومی نیست.</p>',
            'status' => 'draft',
        ])->assertRedirect();

        $this->get(route('seo.articles.index'))
            ->assertOk()
            ->assertSee('راهنمای یادگیری مؤثر')
            ->assertDontSee('مقاله پیش‌نویس');

        $this->get(route('seo.articles.show', $article->slug))
            ->assertOk()
            ->assertSee('راهنمای یادگیری | شیخان')
            ->assertSee('name="description"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:image"', false)
            ->assertSee('تصویر راهنمای یادگیری')
            ->assertSee('<strong>مهم</strong>', false);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee($article->canonical_url, false)
            ->assertDontSee(route('seo.articles.show', 'draft-article'), false);

        $this->actingAs($seo)->patch(route('admin.seo.articles.update', $article), [
            'title' => 'راهنمای به‌روزشده',
            'slug' => 'effective-learning-guide',
            'excerpt' => 'خلاصه به‌روزشده',
            'body' => '<p>محتوای اصلاح‌شده</p>',
            'meta_title' => 'عنوان جدید',
            'meta_description' => 'توضیح جدید',
            'keywords' => 'تمرین, یادگیری',
            'status' => 'archived',
        ])->assertRedirect();

        $this->assertDatabaseHas('seo_articles', [
            'id' => $article->id,
            'title' => 'راهنمای به‌روزشده',
            'status' => 'archived',
        ]);

        $this->get(route('seo.articles.show', $article->slug))->assertNotFound();

        $this->actingAs($seo)->delete(route('admin.seo.articles.destroy', $article))->assertRedirect();
        $this->assertDatabaseMissing('seo_articles', ['id' => $article->id]);
        Storage::disk('public')->assertMissing($featuredImagePath);
        Storage::disk('public')->assertMissing($ogImagePath);
    }

    public function test_scheduled_article_becomes_public_and_enters_sitemap_when_publication_time_arrives(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->post(route('admin.seo.articles.store'), [
            'title' => 'مقاله زمان‌بندی‌شده',
            'slug' => 'scheduled-public-article',
            'body' => '<p>محتوای زمان‌بندی‌شده</p>',
            'status' => 'scheduled',
            'published_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->get(route('seo.articles.index'))->assertDontSee('مقاله زمان‌بندی‌شده');
        $this->get(route('seo.sitemap'))->assertDontSee('scheduled-public-article');

        $this->travel(2)->days();

        $this->get(route('seo.articles.index'))->assertSee('مقاله زمان‌بندی‌شده');
        $this->get(route('seo.articles.show', 'scheduled-public-article'))->assertOk();
        $this->get(route('seo.sitemap'))->assertSee('scheduled-public-article');
    }

    public function test_public_article_index_only_displays_current_active_banners_in_sort_order(): void
    {
        $seo = $this->seoUser();
        Storage::disk('public')->put('seo/banners/first.jpg', 'image');
        Storage::disk('public')->put('seo/banners/second.jpg', 'image');
        Storage::disk('public')->put('seo/banners/expired.jpg', 'image');
        Storage::disk('public')->put('seo/banners/future.jpg', 'image');

        SeoBanner::query()->create([
            'created_by' => $seo->id,
            'title' => 'بنر دوم',
            'alt_text' => 'بنر دوم عمومی',
            'image_path' => 'seo/banners/second.jpg',
            'placement' => 'articles_top',
            'sort_order' => 2,
            'status' => 'active',
        ]);
        SeoBanner::query()->create([
            'created_by' => $seo->id,
            'title' => 'بنر اول',
            'alt_text' => 'بنر اول عمومی',
            'image_path' => 'seo/banners/first.jpg',
            'placement' => 'sitewide',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        SeoBanner::query()->create([
            'created_by' => $seo->id,
            'title' => 'بنر منقضی',
            'alt_text' => 'بنر منقضی',
            'image_path' => 'seo/banners/expired.jpg',
            'placement' => 'articles_top',
            'sort_order' => 0,
            'starts_at' => now()->subDays(4),
            'ends_at' => now()->subDay(),
            'status' => 'active',
        ]);
        SeoBanner::query()->create([
            'created_by' => $seo->id,
            'title' => 'بنر آینده',
            'alt_text' => 'بنر آینده',
            'image_path' => 'seo/banners/future.jpg',
            'placement' => 'articles_top',
            'sort_order' => 0,
            'starts_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $response = $this->get(route('seo.articles.index'))
            ->assertOk()
            ->assertSee('بنر اول عمومی')
            ->assertSee('بنر دوم عمومی')
            ->assertDontSee('بنر منقضی')
            ->assertDontSee('بنر آینده');

        self::assertLessThan(
            strpos($response->getContent(), 'بنر دوم عمومی'),
            strpos($response->getContent(), 'بنر اول عمومی')
        );
    }

    public function test_scheduled_article_requires_a_publication_date_and_duplicate_slugs_are_rejected(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->from(route('admin.seo.articles.index'))
            ->post(route('admin.seo.articles.store'), [
                'title' => 'مقاله زمان‌بندی‌شده',
                'slug' => 'scheduled-article',
                'body' => '<p>متن</p>',
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors('published_at');

        SeoArticle::query()->create([
            'author_id' => $seo->id,
            'title' => 'مقاله موجود',
            'slug' => 'duplicate-article',
            'body' => '<p>متن</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($seo)->from(route('admin.seo.articles.index'))
            ->post(route('admin.seo.articles.store'), [
                'title' => 'مقاله تکراری',
                'slug' => 'duplicate-article',
                'body' => '<p>متن</p>',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_banner_rejects_non_http_external_and_protocol_relative_targets(): void
    {
        $seo = $this->seoUser();
        $payload = [
            'title' => 'بنر با مقصد نامعتبر',
            'alt_text' => 'تصویر بنر',
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
            'placement' => 'articles_top',
            'sort_order' => 0,
            'status' => 'active',
        ];

        foreach (['ftp://example.com/file', '//example.com/path'] as $target) {
            $this->actingAs($seo)
                ->from(route('admin.seo.banners.index'))
                ->post(route('admin.seo.banners.store'), [...$payload, 'target_url' => $target])
                ->assertSessionHasErrors('target_url');
        }

        $this->assertDatabaseCount('seo_banners', 0);
    }

    public function test_sitemap_does_not_list_noindex_articles_even_when_sitemap_is_enabled(): void
    {
        $seo = $this->seoUser();
        SeoSetting::query()->create([
            'id' => 1,
            'site_name' => 'آکادمی شیخان',
            'default_title' => 'آموزش',
            'title_separator' => ' | ',
            'robots_index' => false,
            'robots_follow' => true,
            'sitemap_enabled' => true,
            'updated_by' => $seo->id,
        ]);
        $article = SeoArticle::query()->create([
            'author_id' => $seo->id,
            'title' => 'مقاله بدون ایندکس',
            'slug' => 'noindex-article',
            'body' => '<p>محتوا</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertDontSee($article->slug);
    }
    public function test_seo_user_can_manage_page_metadata_home_hero_banners_and_teacher_sitemap_entries(): void
    {
        $seo = $this->seoUser();
        $this->actingAs($seo)->patch(route('admin.seo.settings.update'), [
            'site_name' => 'آکادمی شیخان',
            'default_title' => 'عنوان پیش‌فرض',
            'title_separator' => ' | ',
            'default_description' => 'توضیح پیش‌فرض',
            'canonical_base_url' => 'https://sheykhan.example',
            'home_sections' => [
                'home_meta_title' => 'خانه شیخان؛ آموزش هدفمند',
                'home_meta_description' => 'توضیحات اختصاصی صفحه اصلی برای موتورهای جست‌وجو',
                'hero_badge' => 'مسیر یادگیری روشن',
                'hero_description' => 'متن معرفی که از پنل SEO کنترل می‌شود.',
                'hero_primary_label' => 'دیدن دوره‌ها',
                'hero_secondary_label' => 'گفت‌وگو با مشاور',
                'categories_eyebrow' => 'موضوع‌های آموزشی',
                'categories_title' => 'از اینجا مسیرت را انتخاب کن',
                'stats_labels' => ['ثبت‌نام فعال', 'درس منتشرشده', 'مدرس متخصص', 'دوره منتشرشده'],
                'latest_courses_title' => 'دوره‌های تازه منتشرشده',
                'popular_courses_title' => 'انتخاب دانش‌آموزان',
                'steps_eyebrow' => 'نقشه راه',
                'steps_title' => 'چهار گام برای پیشرفت',
                'steps_description' => 'توضیح ویرایش‌شده از پنل SEO',
                'steps' => [
                    ['title' => 'گام اول ویرایش‌شده', 'body' => 'توضیح گام اول'],
                    ['title' => 'گام دوم', 'body' => 'توضیح گام دوم'],
                    ['title' => 'گام سوم', 'body' => 'توضیح گام سوم'],
                    ['title' => 'گام چهارم', 'body' => 'توضیح گام چهارم'],
                ],
                'benefits_title' => 'مزیت‌های به‌روزشده',
                'benefits' => [
                    ['title' => 'مزیت اول', 'body' => 'توضیح مزیت اول'],
                    ['title' => 'مزیت دوم', 'body' => 'توضیح مزیت دوم'],
                    ['title' => 'مزیت سوم', 'body' => 'توضیح مزیت سوم'],
                    ['title' => 'مزیت چهارم', 'body' => 'توضیح مزیت چهارم'],
                    ['title' => 'مزیت پنجم', 'body' => 'توضیح مزیت پنجم'],
                    ['title' => 'مزیت ششم', 'body' => 'توضیح مزیت ششم'],
                ],
                'faq_title' => 'پرسش‌های تازه',
                'faq_items' => [
                    ['question' => 'پرسش ثبت‌شده در پنل چیست؟', 'answer' => 'پاسخ ثبت‌شده در پنل'],
                ],
                'blog_page_title' => 'مجله تخصصی شیخان',
                'blog_page_description' => 'مقاله‌های آموزشی و راهنماهای کاربردی',
                'courses_page_title' => 'دوره‌های تخصصی شیخان',
                'courses_page_description' => 'فهرست دوره‌های آموزشی منتشرشده',
                'teachers_page_title' => 'مدرسان آکادمی',
                'teachers_page_description' => 'آشنایی با مدرسان فعال آکادمی',
                'teachers_cta_title' => 'دعوت از مدرسان جدید',
                'teachers_cta_body' => 'برای همکاری با آکادمی اقدام کنید.',
                'teachers_cta_label' => 'درخواست همکاری',
                'contact_page_title' => 'ارتباط با آکادمی',
                'contact_page_description' => 'راه‌های ارتباط با پشتیبانی',
                'contact_topics' => ['پشتیبانی دوره', 'مسائل مالی'],
                'contact_support_title' => 'پشتیبانی به‌روزشده',
                'contact_support_body' => 'توضیح پشتیبانی از پنل SEO',
                'contact_faq_title' => 'پرسش‌های تماس',
                'contact_faq_items' => [
                    ['question' => 'چطور پیام بدهم؟', 'answer' => 'از فرم تماس استفاده کنید.'],
                ],
                'cart_page_title' => 'سبد خرید دوره‌ها',
                'cart_page_description' => 'دوره‌های انتخاب‌شده شما',
            ],
            'social_links' => [
                'instagram' => 'https://instagram.com/sheykhan',
                'telegram' => 'https://t.me/sheykhan',
                'x' => 'https://x.com/sheykhan',
                'youtube' => 'https://youtube.com/@sheykhan',
            ],
            'footer_links' => [
                ['label' => 'دوره‌ها', 'url' => '/courses'],
                ['label' => 'مقالات', 'url' => '/articles'],
            ],
            'robots_index' => '1',
            'robots_follow' => '1',
            'sitemap_enabled' => '1',
        ])->assertRedirect();

        $settings = SeoSetting::query()->firstOrFail();
        $this->assertSame('خانه شیخان؛ آموزش هدفمند', $settings->home_sections['home_meta_title']);
        $this->assertSame('مجله تخصصی شیخان', $settings->home_sections['blog_page_title']);
        $this->assertSame('متن معرفی که از پنل SEO کنترل می‌شود.', $settings->home_sections['hero_description']);
        $this->assertSame('گام اول ویرایش‌شده', $settings->home_sections['steps'][0]['title']);
        $this->assertSame('پرسش ثبت‌شده در پنل چیست؟', $settings->home_sections['faq_items'][0]['question']);
        $this->assertSame('دعوت از مدرسان جدید', $settings->home_sections['teachers_cta_title']);
        $this->assertSame('سبد خرید دوره‌ها', $settings->home_sections['cart_page_title']);
        $this->assertSame('https://instagram.com/sheykhan', $settings->social_links['instagram']);
        $this->assertSame('https://x.com/sheykhan', $settings->social_links['x']);
        $this->assertCount(2, $settings->footer_links);

        $this->get(route('home'))->assertOk()
            ->assertSee('<title>خانه شیخان؛ آموزش هدفمند</title>', false)
            ->assertSee('name="description" content="توضیحات اختصاصی صفحه اصلی برای موتورهای جست‌وجو"', false)
            ->assertSee('مسیر یادگیری روشن')
            ->assertSee('متن معرفی که از پنل SEO کنترل می‌شود.')
            ->assertSee('گام اول ویرایش‌شده')
            ->assertSee('مزیت‌های به‌روزشده')
            ->assertSee('پرسش‌های تازه');

        $this->get(route('public.courses.index'))->assertOk()
            ->assertSee('دوره‌های تخصصی شیخان')
            ->assertSee('فهرست دوره‌های آموزشی منتشرشده');

        $this->get(route('seo.articles.index'))->assertOk()
            ->assertSee('مجله تخصصی شیخان')
            ->assertSee('مقاله‌های آموزشی و راهنماهای کاربردی');

        $this->get(route('public.teachers.index'))->assertOk()
            ->assertSee('مدرسان آکادمی')
            ->assertSee('دعوت از مدرسان جدید')
            ->assertSee('درخواست همکاری');

        $this->get(route('public.contact'))->assertOk()
            ->assertSee('پشتیبانی به‌روزشده')
            ->assertSee('چطور پیام بدهم؟')
            ->assertSee('مسائل مالی');

        $this->get(route('public.cart'))->assertOk()
            ->assertSee('سبد خرید دوره‌ها');

        $teacherRole = Role::query()->where('name', 'teacher')->firstOrFail();
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->roles()->attach($teacherRole);

        $this->get(route('seo.sitemap'))->assertOk()
            ->assertSee('https://sheykhan.example/teachers/'.$teacher->id);
    }

    public function test_seo_user_can_create_home_hero_banner_with_image_position(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->post(route('admin.seo.banners.store'), [
            'title' => 'بنر اصلی خانه',
            'alt_text' => 'معرفی دوره‌های آموزشی',
            'image' => UploadedFile::fake()->image('home-hero.jpg', 1600, 600),
            'image_position' => '42% 50%',
            'placement' => 'home_hero',
            'target_url' => '/courses',
            'sort_order' => 0,
            'status' => 'active',
        ])->assertRedirect();

        $banner = SeoBanner::query()->firstOrFail();
        $this->assertSame('home_hero', $banner->placement);
        $this->assertSame('42% 50%', $banner->image_position);

        $this->get(route('home'))->assertOk()
            ->assertSee('بنر اصلی خانه')
            ->assertSee('42% 50%');
    }

}
