<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoPublicPageContentEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_seo_operator_can_update_public_page_copy_from_the_central_editor(): void
    {
        $seo = User::factory()->create();
        $seo->roles()->attach(Role::query()->where('name', 'seo')->firstOrFail());

        $this->actingAs($seo)
            ->patch(route('admin.seo.settings.update'), [
                'site_name' => 'آکادمی شیخان',
                'default_title' => 'آموزش هدفمند',
                'title_separator' => ' | ',
                'default_description' => 'توضیح عمومی آکادمی',
                'home_sections' => [
                    'home_meta_title' => 'خانه قابل ویرایش',
                    'home_meta_description' => 'توضیح متای صفحه خانه',
                    'hero_badge' => 'نشان جدید صفحه خانه',
                    'hero_description' => 'متن معرفی که از پنل SEO کنترل می‌شود.',
                    'hero_primary_label' => 'مشاهده دوره‌ها',
                    'hero_secondary_label' => 'ارتباط با ما',
                    'categories_eyebrow' => 'موضوع‌های آموزشی',
                    'categories_title' => 'موضوع مورد علاقه را انتخاب کنید',
                    'steps_eyebrow' => 'مسیر پیشرفت',
                    'steps_title' => 'چهار گام یادگیری',
                    'steps_description' => 'توضیح مسیر یادگیری از پنل',
                    'steps' => [
                        ['title' => 'گام ویرایش‌شده اول', 'body' => 'شرح گام اول'],
                        ['title' => 'گام دوم', 'body' => 'شرح گام دوم'],
                        ['title' => 'گام سوم', 'body' => 'شرح گام سوم'],
                        ['title' => 'گام چهارم', 'body' => 'شرح گام چهارم'],
                    ],
                    'benefits_title' => 'مزیت‌های ویرایش‌شده',
                    'benefits' => [
                        ['title' => 'مزیت اول', 'body' => 'شرح مزیت اول'],
                    ],
                    'faq_title' => 'پرسش‌های جدید',
                    'faq_items' => [
                        ['question' => 'سؤال پنل چیست؟', 'answer' => 'پاسخ پنل چیست؟'],
                    ],
                    'blog_page_title' => 'مجله اختصاصی آکادمی',
                    'blog_page_description' => 'توضیح مجله از پنل SEO',
                    'courses_page_title' => 'فهرست دوره‌های به‌روزشده',
                    'courses_page_description' => 'توضیح دوره‌ها از پنل SEO',
                    'teachers_page_title' => 'مدرسان به‌روزشده',
                    'teachers_page_description' => 'توضیح مدرسان از پنل SEO',
                    'teachers_cta_title' => 'دعوت به همکاری مدرس',
                    'teachers_cta_body' => 'شرح دعوت به همکاری',
                    'teachers_cta_label' => 'درخواست همکاری',
                    'contact_page_title' => 'تماس به‌روزشده',
                    'contact_page_description' => 'توضیح صفحه تماس',
                    'contact_topics' => ['پشتیبانی دوره', 'مسائل مالی'],
                    'contact_support_title' => 'پشتیبانی اختصاصی',
                    'contact_support_body' => 'شرح پشتیبانی',
                    'contact_faq_title' => 'پرسش‌های تماس',
                    'contact_faq_items' => [
                        ['question' => 'چطور پیام بدهم؟', 'answer' => 'از فرم تماس استفاده کنید.'],
                    ],
                    'cart_page_title' => 'سبد خرید به‌روزشده',
                    'cart_page_description' => 'توضیح سبد خرید',
                    'stats_labels' => ['ثبت‌نام‌ها', 'درس‌ها', 'مدرسان', 'دوره‌ها'],
                ],
                'social_links' => [
                    'instagram' => 'https://instagram.com/sheykhan',
                    'x' => 'https://x.com/sheykhan',
                    'youtube' => 'https://youtube.com/@sheykhan',
                ],
                'footer_description' => 'توضیح جدید فوتر',
                'footer_links' => [
                    ['label' => 'دوره‌ها', 'url' => '/courses'],
                    ['label' => 'مقالات', 'url' => '/articles'],
                ],
                'robots_index' => '1',
                'robots_follow' => '1',
                'sitemap_enabled' => '1',
            ])
            ->assertRedirect();

        $settings = SeoSetting::query()->firstOrFail();
        $this->assertSame('متن معرفی که از پنل SEO کنترل می‌شود.', $settings->home_sections['hero_description']);
        $this->assertSame('گام ویرایش‌شده اول', $settings->home_sections['steps'][0]['title']);
        $this->assertSame('سؤال پنل چیست؟', $settings->home_sections['faq_items'][0]['question']);
        $this->assertSame('https://x.com/sheykhan', $settings->social_links['x']);

        $this->get(route('home'))->assertOk()
            ->assertSee('نشان جدید صفحه خانه')
            ->assertSee('متن معرفی که از پنل SEO کنترل می‌شود.')
            ->assertSee('گام ویرایش‌شده اول')
            ->assertSee('مزیت‌های ویرایش‌شده')
            ->assertSee('پرسش‌های جدید');

        $this->get(route('public.courses.index'))->assertOk()
            ->assertSee('فهرست دوره‌های به‌روزشده');

        $this->get(route('seo.articles.index'))->assertOk()
            ->assertSee('مجله اختصاصی آکادمی')
            ->assertSee('توضیح مجله از پنل SEO');

        $this->get(route('public.teachers.index'))->assertOk()
            ->assertSee('مدرسان به‌روزشده')
            ->assertSee('دعوت به همکاری مدرس')
            ->assertSee('درخواست همکاری');

        $this->get(route('public.contact'))->assertOk()
            ->assertSee('پشتیبانی اختصاصی')
            ->assertSee('چطور پیام بدهم؟')
            ->assertSee('مسائل مالی');

        $this->get(route('public.cart'))->assertOk()
            ->assertSee('سبد خرید به‌روزشده');
    }

    public function test_seo_content_editor_does_not_grant_the_operator_general_admin_permissions(): void
    {
        $seo = User::factory()->create();
        $seo->roles()->attach(Role::query()->where('name', 'seo')->firstOrFail());

        $this->assertTrue($seo->hasPermissionTo('seo.manage'));
        $this->assertFalse($seo->hasPermissionTo('users.manage'));
        $this->assertFalse($seo->hasPermissionTo('courses.manage'));
        $this->assertFalse($seo->hasPermissionTo('settings.manage'));
    }
}
