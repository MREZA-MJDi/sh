<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    private function teacher(): User
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        return $teacher;
    }

    public function test_admin_can_open_seo_dashboard_and_teacher_cannot_manage_seo(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.seo.index'))
            ->assertOk()
            ->assertSee('مدیریت سئو')
            ->assertSee('مرکز مدیریت سئو')
            ->assertSee('پیش‌نمایش نتیجه جست‌وجو')
            ->assertSee('کد Google Search Console')
            ->assertSee('کنترل خزنده‌ها و نقشه سایت')
            ->assertSee('مدیریت سئو');

        $this->actingAs($this->admin())->get(route('admin.seo.articles.index'))
            ->assertOk()->assertSee('ایجاد مقاله جدید')->assertSee('تصویر Open Graph');

        $this->actingAs($this->admin())->get(route('admin.seo.banners.index'))
            ->assertOk()->assertSee('ثبت بنر جدید')->assertSee('متن جایگزین تصویر');

        $this->actingAs($this->teacher())
            ->get(route('admin.seo.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_seo_settings_and_robots_endpoint_uses_saved_values(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.seo.settings.update'), [
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
        $this->assertSame($admin->id, $settings->updated_by);
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

    public function test_admin_can_create_update_and_delete_banner_with_its_image(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.seo.banners.store'), [
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
        $this->assertSame($admin->id, $banner->created_by);
        $this->assertTrue($banner->open_in_new_tab);

        $this->actingAs($admin)->patch(route('admin.seo.banners.update', $banner), [
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

        $this->actingAs($admin)->delete(route('admin.seo.banners.destroy', $banner))->assertRedirect();
        $this->assertDatabaseMissing('seo_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($banner->image_path);
    }

    public function test_admin_can_manage_articles_and_only_published_articles_are_public(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.seo.articles.store'), [
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
        $this->assertSame($admin->id, $article->author_id);
        $this->assertSame(['یادگیری', 'مطالعه', 'آموزش'], $article->keywords);
        $this->assertNotNull($article->published_at);
        Storage::disk('public')->assertExists($article->featured_image_path);
        Storage::disk('public')->assertExists($article->og_image_path);
        $featuredImagePath = $article->featured_image_path;
        $ogImagePath = $article->og_image_path;
        $this->assertStringContainsString('<strong>مهم</strong>', $article->body);
        $this->assertStringNotContainsString('<script', $article->body);

        $this->actingAs($admin)->post(route('admin.seo.articles.store'), [
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
            ->assertSee(route('seo.articles.show', $article->slug), false)
            ->assertDontSee(route('seo.articles.show', 'draft-article'), false);

        $this->actingAs($admin)->patch(route('admin.seo.articles.update', $article), [
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

        $this->actingAs($admin)->delete(route('admin.seo.articles.destroy', $article))->assertRedirect();
        $this->assertDatabaseMissing('seo_articles', ['id' => $article->id]);
        Storage::disk('public')->assertMissing($featuredImagePath);
        Storage::disk('public')->assertMissing($ogImagePath);
    }

    public function test_scheduled_article_becomes_public_and_enters_sitemap_when_publication_time_arrives(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.seo.articles.store'), [
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
        $admin = $this->admin();
        Storage::disk('public')->put('seo/banners/first.jpg', 'image');
        Storage::disk('public')->put('seo/banners/second.jpg', 'image');
        Storage::disk('public')->put('seo/banners/expired.jpg', 'image');
        Storage::disk('public')->put('seo/banners/future.jpg', 'image');

        SeoBanner::query()->create([
            'created_by' => $admin->id,
            'title' => 'بنر دوم',
            'alt_text' => 'بنر دوم عمومی',
            'image_path' => 'seo/banners/second.jpg',
            'placement' => 'articles_top',
            'sort_order' => 2,
            'status' => 'active',
        ]);
        SeoBanner::query()->create([
            'created_by' => $admin->id,
            'title' => 'بنر اول',
            'alt_text' => 'بنر اول عمومی',
            'image_path' => 'seo/banners/first.jpg',
            'placement' => 'sitewide',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        SeoBanner::query()->create([
            'created_by' => $admin->id,
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
            'created_by' => $admin->id,
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
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.seo.articles.index'))
            ->post(route('admin.seo.articles.store'), [
                'title' => 'مقاله زمان‌بندی‌شده',
                'slug' => 'scheduled-article',
                'body' => '<p>متن</p>',
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors('published_at');

        SeoArticle::query()->create([
            'author_id' => $admin->id,
            'title' => 'مقاله موجود',
            'slug' => 'duplicate-article',
            'body' => '<p>متن</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->from(route('admin.seo.articles.index'))
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
        $admin = $this->admin();
        $payload = [
            'title' => 'بنر با مقصد نامعتبر',
            'alt_text' => 'تصویر بنر',
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
            'placement' => 'articles_top',
            'sort_order' => 0,
            'status' => 'active',
        ];

        foreach (['ftp://example.com/file', '//example.com/path'] as $target) {
            $this->actingAs($admin)
                ->from(route('admin.seo.banners.index'))
                ->post(route('admin.seo.banners.store'), [...$payload, 'target_url' => $target])
                ->assertSessionHasErrors('target_url');
        }

        $this->assertDatabaseCount('seo_banners', 0);
    }

    public function test_sitemap_does_not_list_noindex_articles_even_when_sitemap_is_enabled(): void
    {
        $admin = $this->admin();
        SeoSetting::query()->create([
            'id' => 1,
            'site_name' => 'آکادمی شیخان',
            'default_title' => 'آموزش',
            'title_separator' => ' | ',
            'robots_index' => false,
            'robots_follow' => true,
            'sitemap_enabled' => true,
            'updated_by' => $admin->id,
        ]);
        $article = SeoArticle::query()->create([
            'author_id' => $admin->id,
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

}
