<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SeoArticle;
use App\Models\SeoSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class SeoWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => 'seo'],
            [
                'label' => 'کارشناس سئو',
                'description' => 'مدیریت اختصاصی محتوای عمومی، بنرها و تنظیمات سئو',
            ]
        );

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'seo.manage'],
            [
                'label' => 'مدیریت سئو، بنرها و مقاله‌ها',
                'group' => 'seo',
            ]
        );

        // This role is deliberately isolated from operational admin permissions.
        $role->permissions()->sync([$permission->id]);

        $seoUser = $this->provisionOperator($role);

        // Materialize the singleton row up front instead of relying on the
        // first public page request to create SEO configuration at runtime.
        SeoSetting::current();

        $this->seedStarterArticles($seoUser);
    }

    private function provisionOperator(Role $role): ?User
    {
        $email = trim((string) config('seo.operator.email'));
        $password = config('seo.operator.password');
        $name = trim((string) config('seo.operator.name', 'کارشناس سئو'));

        if ($email === '' && (! is_string($password) || $password === '')) {
            return null;
        }

        if ($email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException(
                'برای ساخت حساب SEO باید هر دو مقدار SHIKHAN_SEO_EMAIL و SHIKHAN_SEO_PASSWORD تنظیم شوند.'
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SHIKHAN_SEO_EMAIL must be a valid email address.');
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('SHIKHAN_SEO_PASSWORD must contain at least 12 characters.');
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing && $existing->roles()->whereIn('name', ['admin', 'teacher', 'student', 'parent'])->exists()) {
            throw new RuntimeException('The SEO account email is already assigned to another application role.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'کارشناس سئو',
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );

        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function seedStarterArticles(?User $author): void
    {
        $articles = [
            [
                'slug' => 'barname-rizi-darsi-movafagh',
                'title' => 'چطور یک برنامه‌ریزی درسی قابل اجرا داشته باشیم؟',
                'excerpt' => 'با هدف‌گذاری کوتاه‌مدت، تقسیم درست زمان و مرور منظم، برنامه‌ای بسازید که در زندگی روزمره قابل اجرا بماند.',
                'body' => '<h2>از هدف روشن شروع کنید</h2><p>برنامه درسی زمانی کاربردی است که بر اساس زمان واقعی، سطح فعلی و هدف آموزشی شما نوشته شود. به‌جای فهرست‌کردن ساعت‌های طولانی، برای هر هفته چند نتیجه مشخص و قابل سنجش تعیین کنید.</p><h2>زمان را به واحدهای کوچک تقسیم کنید</h2><p>برای هر نوبت مطالعه یک موضوع مشخص در نظر بگیرید و میان مطالعه، حل تمرین و استراحت تعادل ایجاد کنید. بهتر است کارهای دشوار را در ساعتی قرار دهید که تمرکز بیشتری دارید.</p><h2>مرور و ارزیابی را در برنامه بگنجانید</h2><p>در پایان هر هفته، مباحثی را که آموخته‌اید مرور کنید و با چند تمرین کوتاه میزان یادگیری را بسنجید. برنامه را بر اساس نتیجه واقعی اصلاح کنید، نه بر اساس زمان ایده‌آل و غیرقابل اجرا.</p><h2>پیوستگی مهم‌تر از فشردگی است</h2><p>یک برنامه متعادل که بیشتر روزها اجرا شود، معمولاً از برنامه سنگینی که پس از چند روز رها می‌شود مفیدتر است. برای جبران عقب‌ماندگی نیز زمان انعطاف‌پذیر در نظر بگیرید.</p>',
                'meta_title' => 'برنامه‌ریزی درسی قابل اجرا؛ راهنمای گام‌به‌گام',
                'meta_description' => 'روش هدف‌گذاری، تقسیم زمان، مرور و ارزیابی برای ساخت یک برنامه درسی واقع‌بینانه و قابل اجرا.',
                'keywords' => ['برنامه‌ریزی درسی', 'روش مطالعه', 'مدیریت زمان', 'آموزش'],
            ],
            [
                'slug' => 'moroor-faale-va-yadgiri-paayedar',
                'title' => 'مرور فعال؛ راهی برای ماندگاری بهتر آموخته‌ها',
                'excerpt' => 'به‌جای چندبار خواندن منفعلانه، با پرسش‌سازی، بازیابی از حافظه و حل تمرین، آموخته‌ها را عمیق‌تر تثبیت کنید.',
                'body' => '<h2>مرور فعال چیست؟</h2><p>در مرور فعال، دانش‌آموز به‌جای نگاه‌کردن دوباره به متن، تلاش می‌کند مفهوم را از حافظه بازیابی کند. پاسخ‌دادن به سؤال، توضیح‌دادن یک موضوع با زبان خود و حل تمرین نمونه‌هایی از این روش هستند.</p><h2>پس از مطالعه، کتاب را ببندید</h2><p>چند دقیقه بعد از مطالعه، نکته‌های اصلی را بدون نگاه‌کردن به جزوه یادداشت کنید. سپس یادداشت خود را با منبع مقایسه کنید تا بخش‌های ناقص مشخص شوند.</p><h2>مرور را در چند نوبت پخش کنید</h2><p>به‌جای جمع‌کردن همه مرورها در یک شب، مباحث را در فاصله‌های زمانی مناسب دوباره بررسی کنید. زمان‌بندی دقیق می‌تواند با دشواری درس و میزان تسلط شما تغییر کند.</p><h2>تمرین را به بازخورد تبدیل کنید</h2><p>پس از حل سؤال، فقط به درست یا غلط بودن پاسخ اکتفا نکنید. دلیل اشتباه را پیدا کنید، روش حل را اصلاح کنید و چند روز بعد دوباره همان مهارت را با سؤال دیگری بسنجید.</p>',
                'meta_title' => 'مرور فعال و تثبیت یادگیری؛ روش‌های کاربردی',
                'meta_description' => 'با بازیابی از حافظه، پرسش‌سازی، حل تمرین و مرور فاصله‌دار، یادگیری را عمیق‌تر و ماندگارتر کنید.',
                'keywords' => ['مرور فعال', 'یادگیری پایدار', 'تکنیک مطالعه', 'حل تمرین'],
            ],
        ];

        foreach ($articles as $article) {
            $record = SeoArticle::query()->firstOrCreate(
                ['slug' => $article['slug']],
                [
                    ...$article,
                    'author_id' => $author?->id,
                    'status' => 'published',
                    'published_at' => now()->subDays(2),
                ]
            );

            if ($author && $record->author_id === null) {
                $record->forceFill(['author_id' => $author->id])->save();
            }
        }
    }
}
