<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => ['label' => 'مدیر سامانه', 'description' => 'دسترسی کامل مدیریتی به سامانه'],
            'teacher' => ['label' => 'معلم', 'description' => 'مدیریت کلاس‌ها و دوره‌های تخصیص‌یافته'],
            'student' => ['label' => 'دانش‌آموز', 'description' => 'دسترسی به اطلاعات و خدمات آموزشی شخصی'],
            'parent' => ['label' => 'ولی دانش‌آموز', 'description' => 'پیگیری وضعیت آموزشی فرزندان متصل‌شده'],
        ];

        $catalogue = [
            'dashboard.view' => ['مشاهده داشبورد', 'dashboard'],
            'users.view' => ['مشاهده کاربران', 'users'],
            'users.manage' => ['مدیریت کاربران', 'users'],
            'students.view.assigned' => ['مشاهده دانش‌آموزان کلاس‌های تخصیص‌یافته', 'students'],
            'students.view.linked' => ['مشاهده فرزندان متصل‌شده', 'students'],
            'classes.view.all' => ['مشاهده همه کلاس‌ها', 'classes'],
            'classes.view.assigned' => ['مشاهده کلاس‌های تخصیص‌یافته', 'classes'],
            'classes.view.enrolled' => ['مشاهده کلاس‌های ثبت‌نام‌شده', 'classes'],
            'classes.manage' => ['مدیریت همه کلاس‌ها', 'classes'],
            'classes.manage.assigned' => ['مدیریت کلاس‌های تخصیص‌یافته', 'classes'],
            'courses.view.all' => ['مشاهده همه دوره‌ها', 'courses'],
            'courses.view.assigned' => ['مشاهده دوره‌های تخصیص‌یافته', 'courses'],
            'courses.manage' => ['مدیریت دوره‌ها', 'courses'],
            'attendance.view.all' => ['مشاهده همه حضور و غیاب‌ها', 'attendance'],
            'attendance.view.assigned' => ['مشاهده حضور و غیاب کلاس‌های خود', 'attendance'],
            'attendance.view.own' => ['مشاهده حضور و غیاب شخصی', 'attendance'],
            'attendance.view.children' => ['مشاهده حضور و غیاب فرزندان', 'attendance'],
            'attendance.manage.all' => ['مدیریت حضور و غیاب همه کلاس‌ها', 'attendance'],
            'attendance.manage.assigned' => ['ثبت حضور و غیاب کلاس‌های خود', 'attendance'],
            'homework.view.all' => ['مشاهده همه تکالیف', 'homework'],
            'homework.view.assigned' => ['مشاهده تکالیف کلاس‌های خود', 'homework'],
            'homework.view.own' => ['مشاهده تکالیف شخصی', 'homework'],
            'homework.view.children' => ['مشاهده تکالیف فرزندان', 'homework'],
            'homework.manage.all' => ['مدیریت همه تکالیف', 'homework'],
            'homework.manage.assigned' => ['مدیریت تکالیف کلاس‌های خود', 'homework'],
            'homework.submit' => ['ارسال پاسخ تکلیف', 'homework'],
            'notes.manage.own' => ['مدیریت یادداشت‌های شخصی', 'notes'],
            'homework.grade' => ['تصحیح و نمره‌دهی تکالیف', 'homework'],
            'exams.view.all' => ['مشاهده همه آزمون‌ها', 'exams'],
            'exams.view.own' => ['مشاهده نتایج شخصی آزمون‌ها', 'exams'],
            'exams.view.children' => ['مشاهده نتایج آزمون فرزندان', 'exams'],
            'exams.manage' => ['مدیریت آزمون‌ها', 'exams'],
            'exams.manage.assigned' => ['مدیریت آزمون‌های کلاس‌های خود', 'exams'],
            'resources.view.all' => ['مشاهده همه منابع', 'resources'],
            'resources.view.enrolled' => ['مشاهده منابع مجاز', 'resources'],
            'resources.manage' => ['مدیریت منابع و محصولات', 'resources'],
            'resources.manage.assigned' => ['بارگذاری منابع برای دوره‌های خود', 'resources'],
            'consultations.view.all' => ['مشاهده همه درخواست‌های مشاوره', 'consultations'],
            'consultations.view.assigned' => ['مشاهده جلسات مرتبط', 'consultations'],
            'consultations.view.children' => ['مشاهده جلسات فرزندان', 'consultations'],
            'consultations.manage' => ['مدیریت درخواست‌های مشاوره', 'consultations'],
            'consultations.create' => ['ثبت درخواست مشاوره', 'consultations'],
            'messages.view.all' => ['مشاهده همه پیام‌ها', 'messages'],
            'messages.view.assigned' => ['مشاهده پیام‌های مرتبط با کلاس', 'messages'],
            'messages.view.own' => ['مشاهده پیام‌های شخصی', 'messages'],
            'messages.send.own' => ['ارسال و مدیریت پیام‌های شخصی', 'messages'],
            'messages.view.children' => ['مشاهده پیام‌های مرتبط با فرزندان', 'messages'],
            'messages.reply' => ['پاسخ به پیام‌ها', 'messages'],
            'announcements.view' => ['مشاهده اطلاعیه‌ها', 'announcements'],
            'announcements.manage' => ['مدیریت اطلاعیه‌ها', 'announcements'],
            'videos.view.assigned' => ['مشاهده ویدیوهای کلاس‌های خود', 'videos'],
            'videos.manage.assigned' => ['مدیریت ویدیوهای کلاس‌های خود', 'videos'],
            'integrations.manage' => ['مدیریت اتصال سرویس‌های خارجی', 'integrations'],
            'settings.manage' => ['مدیریت تنظیمات سامانه', 'settings'],
            'profile.update' => ['ویرایش پروفایل شخصی', 'profile'],
        ];

        $permissionModels = [];
        foreach ($catalogue as $name => [$label, $group]) {
            $permissionModels[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['label' => $label, 'group' => $group]
            );
        }

        $roleModels = [];
        foreach ($roles as $name => $data) {
            $roleModels[$name] = Role::updateOrCreate(['name' => $name], $data);
        }

        $assignments = [
            'admin' => array_keys($catalogue),
            'teacher' => [
                'dashboard.view', 'students.view.assigned', 'classes.view.assigned',
                'courses.view.assigned', 'classes.manage.assigned', 'exams.manage.assigned',
                'resources.manage.assigned', 'attendance.view.assigned', 'attendance.manage.assigned',
                'homework.view.assigned', 'homework.manage.assigned', 'homework.grade',
                'messages.view.assigned', 'messages.reply',
                'announcements.view', 'videos.view.assigned', 'videos.manage.assigned', 'profile.update',
            ],
            'student' => [
                'dashboard.view', 'classes.view.enrolled', 'homework.view.own', 'homework.submit',
                'attendance.view.own', 'exams.view.own', 'resources.view.enrolled', 'notes.manage.own',
                'messages.view.own', 'messages.send.own', 'announcements.view', 'profile.update',
            ],
            'parent' => [
                'dashboard.view', 'students.view.linked', 'attendance.view.children',
                'homework.view.children', 'exams.view.children', 'resources.view.enrolled',
                'consultations.view.children', 'consultations.create', 'messages.view.children', 'messages.send.own',
                'announcements.view', 'profile.update',
            ],
        ];

        foreach ($assignments as $roleName => $permissionNames) {
            $roleModels[$roleName]->permissions()->sync(
                collect($permissionNames)->map(fn ($name) => $permissionModels[$name]->id)->all()
            );
        }

        $email = env('SHIKHAN_ADMIN_EMAIL');
        $password = env('SHIKHAN_ADMIN_PASSWORD');

        if (is_string($email) && $email !== '' && is_string($password) && strlen($password) >= 12) {
            $admin = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => env('SHIKHAN_ADMIN_NAME', 'مدیر سامانه'),
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]
            );

            $admin->roles()->syncWithoutDetaching([$roleModels['admin']->id]);
        }
    }
}
