<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Classroom;
use App\Models\Role;
use App\Services\LegacyWordPressPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class ImportLegacyAcademyData extends Command
{
    protected $signature = 'sh:import-legacy-academy
        {--connection=legacy : Read-only source connection}
        {--legacy-academy-id= : Academy ID in the old database}
        {--apply : Write the reviewed import into the current database}';

    protected $description = 'Dry-run-first import of old accounts, courses, classrooms and enrollments';

    private const SOURCE = 'sheykhan';
    private const ROLES = ['student' => 'student', 'parent' => 'parent', 'teacher' => 'teacher', 'admin' => 'admin'];

    private array $targetRoles = [];
    private array $currentRoles = [];
    private array $paidTotals = [];

    public function handle(LegacyWordPressPassword $passwords): int
    {
        $connection = (string) $this->option('connection');
        $academyId = filter_var($this->option('legacy-academy-id'), FILTER_VALIDATE_INT);

        if (! $academyId || $academyId < 1) {
            $this->error('برای جلوگیری از انتقال داده بین آموزشگاه‌ها، --legacy-academy-id الزامی است.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('legacy_import_maps')) {
            $this->error('جدول legacy_import_maps وجود ندارد؛ ابتدا migrationهای مقصد را اجرا کنید.');
            return self::FAILURE;
        }

        try {
            $source = DB::connection($connection);
            $source->getPdo();
        } catch (Throwable $exception) {
            $this->error('اتصال منبع برقرار نشد. LEGACY_DB_* و دسترسی SELECT را بررسی کنید.');
            $this->line(Str::limit($exception->getMessage(), 500));
            return self::FAILURE;
        }

        $required = [
            'users', 'roles', 'role_user', 'academy_user', 'parent_student',
            'courses', 'course_teacher', 'classrooms', 'classroom_teacher',
            'classroom_student', 'course_enrollments', 'financial_transactions',
        ];
        $missing = collect($required)
            ->reject(fn ($table) => Schema::connection($connection)->hasTable($table))
            ->values()->all();

        if ($missing !== []) {
            $this->error('ساختار دیتابیس قدیمی ناقص است: '.implode(', ', $missing));
            return self::FAILURE;
        }

        $this->targetRoles = Role::query()->whereIn('name', array_values(self::ROLES))->pluck('id', 'name')->all();
        if (count($this->targetRoles) !== count(self::ROLES)) {
            $this->error('نقش‌های مقصد کامل نیستند؛ ابتدا RolePermissionSeeder را اجرا کنید.');
            return self::FAILURE;
        }

        try {
            $data = $this->snapshot($source, (int) $academyId, $passwords);
            $conflicts = $this->identityConflicts($data['users']);
        } catch (Throwable $exception) {
            $this->error('پیش‌بررسی منبع انجام نشد: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->report($data, $conflicts, $connection, (int) $academyId);

        if ($conflicts !== []) {
            $this->error('تعارض هویتی وجود دارد؛ هیچ تغییری اعمال نشد.');
            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->comment('Dry-run تمام شد؛ دیتابیس مقصد تغییر نکرد. برای اعمال پس از بازبینی گزارش، --apply را اضافه کنید.');
            return self::SUCCESS;
        }

        $this->currentRoles = $data['roles'];
        $this->paidTotals = $data['paidTotals'];

        try {
            $counts = DB::transaction(function () use ($data): array {
                $counts = [
                    'users' => 0, 'role_links' => 0, 'guardian_links' => 0,
                    'courses' => 0, 'classrooms' => 0, 'roster_links' => 0,
                    'enrollments' => 0, 'skipped_classrooms' => 0,
                ];

                $this->importUsers($data['users'], $counts);
                $this->importGuardianLinks($data['parents'], $counts);
                $this->importCourses($data['courses'], $data['course_teachers'], $counts);
                $this->importEnrollments($data['enrollments'], $counts);
                $classMaps = $this->importClassrooms($data['classrooms'], $data['classroom_teachers'], $counts);
                $this->importRosters($data['rosters'], $classMaps, $counts);

                DB::table('legacy_import_maps')
                    ->where('source', self::SOURCE)
                    ->update(['imported_at' => now(), 'updated_at' => now()]);

                return $counts;
            }, 3);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('انتقال Rollback شد؛ دیتابیس مقصد به وضعیت قبل از عملیات بازگشت.');
            $this->line(Str::limit($exception->getMessage(), 700));
            return self::FAILURE;
        }

        $this->info('انتقال اطلاعات انجام شد.');
        $this->table(['نوع داده', 'تعداد جدید'], collect($counts)->map(fn ($count, $key) => [$key, $count])->values()->all());
        $this->warn('دوره پولی بدون تراکنش تاریخی تکمیل‌شده و پرداخت کافی، با وضعیت pending_payment وارد می‌شود.');
        return self::SUCCESS;
    }

    private function snapshot($source, int $academyId, LegacyWordPressPassword $passwords): array
    {
        $courses = $source->table('courses')->where('academy_id', $academyId)->orderBy('id')->get();
        $courseIds = $courses->pluck('id')->all();

        $classrooms = $source->table('classrooms')->where('academy_id', $academyId)->orderBy('id')->get();
        $classIds = $classrooms->pluck('id')->all();

        $enrollments = $courseIds === [] ? collect() : $source->table('course_enrollments')->whereIn('course_id', $courseIds)->orderBy('id')->get();
        $rosters = $classIds === [] ? collect() : $source->table('classroom_student')->whereIn('classroom_id', $classIds)->where('status', 'active')->get();
        $courseTeachers = $courseIds === [] ? collect() : $source->table('course_teacher')->whereIn('course_id', $courseIds)->orderByDesc('is_primary')->get();
        $classTeachers = $classIds === [] ? collect() : $source->table('classroom_teacher')->whereIn('classroom_id', $classIds)->get();

        $roles = [];
        foreach ($source->table('academy_user')->where('academy_id', $academyId)->where('status', 'active')->get(['user_id', 'role']) as $membership) {
            $this->addRole($roles, (int) $membership->user_id, (string) $membership->role);
        }

        foreach ($rosters as $row) $this->addRole($roles, (int) $row->student_id, 'student');
        foreach ($enrollments as $row) $this->addRole($roles, (int) $row->student_id, 'student');
        foreach ($courseTeachers as $row) $this->addRole($roles, (int) $row->teacher_id, 'teacher');
        foreach ($classTeachers as $row) $this->addRole($roles, (int) $row->teacher_id, 'teacher');

        $studentIds = $rosters->pluck('student_id')->merge($enrollments->pluck('student_id'))->unique()->values()->all();
        $parents = $studentIds === [] ? collect() : $source->table('parent_student')->whereIn('student_id', $studentIds)->orderBy('parent_id')->get();
        foreach ($parents as $row) {
            $this->addRole($roles, (int) $row->parent_id, 'parent');
            $this->addRole($roles, (int) $row->student_id, 'student');
        }

        $ids = array_keys($roles);
        if ($ids !== []) {
            foreach ($source->table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->whereIn('role_user.user_id', $ids)
                ->get(['role_user.user_id', 'roles.slug']) as $row) {
                $this->addRole($roles, (int) $row->user_id, (string) $row->slug);
            }
        }

        $ids = array_keys($roles);
        $users = $ids === [] ? collect() : $source->table('users')->whereIn('id', $ids)->orderBy('id')->get();

        foreach ($users as $user) {
            if (blank($user->name) || blank($user->password)) {
                throw new \RuntimeException('حساب legacy #'.$user->id.' نام یا گذرواژه ندارد.');
            }

            $hash = (string) $user->password;
            if (! $passwords->supportsHashFormat($hash) && !(str_starts_with($hash, '$2y$') && strlen($hash) === 60)) {
                throw new \RuntimeException('فرمت هش گذرواژه حساب #'.$user->id.' ناشناخته است؛ واردکردن متوقف شد.');
            }
        }

        $paidTotals = $source->table('financial_transactions')
            ->whereNotNull('enrollment_id')
            ->where('status', 'completed')
            ->where('amount', '>', 0)
            ->get(['enrollment_id', 'amount'])
            ->groupBy('enrollment_id')
            ->map(fn (Collection $rows) => (float) $rows->sum('amount'))
            ->all();

        return compact('users', 'roles', 'parents', 'courses', 'classrooms', 'courseTeachers', 'classTeachers', 'rosters', 'enrollments', 'paidTotals');
    }

    private function addRole(array &$roles, int $userId, string $legacyRole): void
    {
        if (isset(self::ROLES[$legacyRole])) {
            $roles[$userId] ??= [];
            $roles[$userId][self::ROLES[$legacyRole]] = true;
        }
    }

    private function identityConflicts(Collection $users): array
    {
        $conflicts = [];
        $emails = [];
        $phones = [];

        foreach ($users as $user) {
            if ($this->mapped('user', (string) $user->id)) continue;

            $email = mb_strtolower(trim((string) ($user->email ?? '')));
            $phone = $this->phone((string) ($user->mobile ?? ''));

            if ($email !== '') {
                if (isset($emails[$email]) || DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $conflicts[] = 'ایمیل تکراری یا موجود در مقصد: '.$email;
                }
                $emails[$email] = true;
            }
            if ($phone !== '') {
                if (isset($phones[$phone]) || DB::table('users')->where('phone', $phone)->exists()) {
                    $conflicts[] = 'موبایل تکراری یا موجود در مقصد: '.$phone;
                }
                $phones[$phone] = true;
            }
            if ($email === '' && $phone === '') {
                $conflicts[] = 'حساب #'.$user->id.' هیچ ایمیل یا موبایل قابل ورود ندارد.';
            }
        }

        return array_values(array_unique($conflicts));
    }

    private function report(array $data, array $conflicts, string $connection, int $academyId): void
    {
        $roleCounts = ['student' => 0, 'parent' => 0, 'teacher' => 0, 'admin' => 0];
        foreach ($data['roles'] as $roles) foreach (array_keys($roles) as $role) $roleCounts[$role]++;

        $paidPending = $data['enrollments']->filter(function ($enrollment) use ($data): bool {
            $course = $data['courses']->firstWhere('id', $enrollment->course_id);
            if (! $course) return false;
            $paidCourse = (($course->access_type ?? null) === 'paid') || (float) ($course->price ?? 0) > 0;
            $required = (float) ($enrollment->price_amount ?? $course->price ?? 0);
            $verified = (float) ($data['paidTotals'][$enrollment->id] ?? 0);
            return $paidCourse && (strtolower((string) ($enrollment->payment_status ?? 'unpaid')) !== 'paid'
                || (float) ($enrollment->paid_amount ?? 0) < $required || $verified < $required);
        })->count();

        $this->info('گزارش Dry-run؛ دیتابیس منبع فقط خوانده شد.');
        $this->table(['مورد', 'تعداد'], [
            ['Connection', $connection], ['Legacy academy ID', $academyId],
            ['حساب‌ها', $data['users']->count()],
            ['دانش‌آموز', $roleCounts['student']], ['اولیا', $roleCounts['parent']], ['معلم', $roleCounts['teacher']],
            ['دوره‌ها', $data['courses']->count()], ['کلاس‌ها', $data['classrooms']->count()],
            ['ارتباط ولی/دانش‌آموز', $data['parents']->count()], ['عضویت فعال کلاس', $data['rosters']->count()],
            ['ثبت‌نام دوره', $data['enrollments']->count()], ['پرداخت‌های نیازمند بررسی', $paidPending],
            ['تعارض هویتی', count($conflicts)],
        ]);

        foreach (array_slice($conflicts, 0, 20) as $conflict) $this->warn($conflict);
    }

    private function importUsers(Collection $users, array &$counts): void
    {
        foreach ($users as $legacy) {
            $legacyId = (string) $legacy->id;
            $targetId = $this->mapped('user', $legacyId);

            if (! $targetId) {
                $email = trim((string) ($legacy->email ?? ''));
                if ($email === '') $email = 'legacy-'.$legacyId.'@accounts.invalid';

                $now = now();
                $targetId = (int) DB::table('users')->insertGetId([
                    'name' => (string) $legacy->name,
                    'email' => $email,
                    'email_verified_at' => null,
                    // Raw SQL keeps WordPress phpass hashes intact until first successful login.
                    'password' => (string) $legacy->password,
                    'remember_token' => null,
                    'created_at' => $legacy->created_at ?? $now,
                    'updated_at' => $now,
                    'phone' => ($phone = $this->phone((string) ($legacy->mobile ?? ''))) !== '' ? $phone : null,
                    'is_active' => strtolower((string) ($legacy->status ?? 'active')) === 'active',
                    'grade' => null, 'subject' => null,
                    'profile_note' => 'حساب منتقل‌شده از سامانه قبلی',
                    'last_login_at' => null, 'permissions_customized' => false,
                ]);

                $this->saveMap('user', $legacyId, $targetId);
                $counts['users']++;
            }

            foreach (array_keys($this->currentRoles[$legacyId] ?? []) as $roleName) {
                $inserted = DB::table('role_user')->insertOrIgnore([
                    'user_id' => $targetId, 'role_id' => $this->targetRoles[$roleName],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $counts['role_links'] += $inserted;
            }
        }
    }

    private function importGuardianLinks(Collection $links, array &$counts): void
    {
        foreach ($links as $link) {
            $guardian = $this->mapped('user', (string) $link->parent_id);
            $student = $this->mapped('user', (string) $link->student_id);
            if (! $guardian || ! $student) continue;

            $counts['guardian_links'] += DB::table('guardian_student')->insertOrIgnore([
                'guardian_id' => $guardian, 'student_id' => $student,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function importCourses(Collection $courses, Collection $teacherRows, array &$counts): void
    {
        $byCourse = $teacherRows->groupBy('course_id');

        foreach ($courses as $legacy) {
            if ($this->mapped('course', (string) $legacy->id)) continue;

            $teacherId = null;
            foreach ($byCourse->get($legacy->id, collect()) as $link) {
                $teacherId = $this->mapped('user', (string) $link->teacher_id);
                if ($teacherId) break;
            }

            $paid = (($legacy->access_type ?? null) === 'paid') || (float) ($legacy->price ?? 0) > 0;
            $published = ($legacy->status ?? null) === 'published'
                && filled($legacy->published_at ?? null)
                && \Carbon\Carbon::parse($legacy->published_at)->isPast();

            $course = Course::query()->create([
                'title' => (string) $legacy->title,
                'slug' => Str::limit(Str::slug((string) ($legacy->slug ?: $legacy->title)), 120, '').'-legacy-'.$legacy->id,
                'description' => $legacy->description ?? $legacy->short_description ?? null,
                'subject' => null,
                'grade' => $legacy->level ?? null,
                'teacher_id' => $teacherId,
                'type' => $paid ? 'paid' : 'free',
                'price' => (int) round((float) ($legacy->price ?? 0)),
                'delivery_type' => filled($legacy->spotplayer_course_id ?? null) ? 'spotplayer' : 'onsite',
                'spotplayer_course_id' => $legacy->spotplayer_course_id ?? null,
                'status' => $published ? 'published' : (($legacy->status ?? null) === 'archived' ? 'archived' : 'draft'),
            ]);
            $this->saveMap('course', (string) $legacy->id, $course->id);
            $counts['courses']++;
        }
    }

    private function importEnrollments(Collection $enrollments, array &$counts): void
    {
        foreach ($enrollments as $legacy) {
            $courseId = $this->mapped('course', (string) $legacy->course_id);
            $studentId = $this->mapped('user', (string) $legacy->student_id);
            if (! $courseId || ! $studentId) continue;

            if (CourseEnrollment::query()->where('course_id', $courseId)->where('student_id', $studentId)->exists()) continue;

            $course = Course::query()->findOrFail($courseId);
            $status = $this->enrollmentStatus($legacy, $course);

            CourseEnrollment::query()->create([
                'course_id' => $courseId, 'student_id' => $studentId,
                'source' => 'legacy_import', 'status' => $status,
                'granted_by' => null,
                'source_reference' => 'sheykhan:course_enrollments:'.$legacy->id,
                'granted_at' => $status === 'active' ? ($legacy->started_at ?? now()) : null,
                'expires_at' => null,
            ]);

            $newId = CourseEnrollment::query()->where('course_id', $courseId)->where('student_id', $studentId)->value('id');
            $this->saveMap('course_enrollment', (string) $legacy->id, (int) $newId);
            $counts['enrollments']++;
        }
    }

    private function enrollmentStatus(object $legacy, Course $course): string
    {
        $oldStatus = strtolower((string) ($legacy->status ?? 'pending'));
        if (in_array($oldStatus, ['completed', 'complete'], true)) return 'completed';
        if (in_array($oldStatus, ['cancelled', 'canceled', 'revoked', 'archived'], true)) return 'revoked';
        if ($oldStatus !== 'active') return 'pending_payment';
        if ($course->type === 'free') return 'active';

        $required = (float) ($legacy->price_amount ?? $course->price);
        $paid = (float) ($legacy->paid_amount ?? 0);
        $verified = (float) ($this->paidTotals[(string) $legacy->id] ?? 0);

        return strtolower((string) ($legacy->payment_status ?? 'unpaid')) === 'paid'
            && $paid >= $required && $verified >= $required
            ? 'active'
            : 'pending_payment';
    }

    private function importClassrooms(Collection $classrooms, Collection $teacherRows, array &$counts): array
    {
        $byClass = $teacherRows->groupBy('classroom_id');
        $maps = [];

        foreach ($classrooms as $legacy) {
            $legacyId = (string) $legacy->id;
            $mapped = $this->mapped('classroom', $legacyId);
            if ($mapped) {
                $maps[$legacyId] = $mapped;
                continue;
            }

            $courseId = $this->mapped('course', (string) $legacy->course_id);
            if (! $courseId) {
                $counts['skipped_classrooms']++;
                continue;
            }

            $teacherId = null;
            foreach ($byClass->get($legacy->id, collect()) as $link) {
                $teacherId = $this->mapped('user', (string) $link->teacher_id);
                if ($teacherId) break;
            }
            $teacherId ??= Course::query()->whereKey($courseId)->value('teacher_id');

            if (! $teacherId) {
                $counts['skipped_classrooms']++;
                continue;
            }

            $creatorId = filled($legacy->created_by ?? null) ? $this->mapped('user', (string) $legacy->created_by) : null;
            $classroom = Classroom::query()->create([
                'course_id' => $courseId,
                'teacher_id' => $teacherId,
                'name' => (string) ($legacy->title ?: 'کلاس '.$legacyId),
                'schedule' => null,
                'meeting_url' => null,
                'status' => ($legacy->status ?? 'active') === 'active' ? 'active' : 'archived',
                'created_by' => $creatorId,
            ]);
            $this->saveMap('classroom', $legacyId, $classroom->id);
            $maps[$legacyId] = $classroom->id;
            $counts['classrooms']++;
        }

        return $maps;
    }

    private function importRosters(Collection $rosters, array $classMaps, array &$counts): void
    {
        foreach ($rosters as $legacy) {
            $classroomId = $classMaps[(string) $legacy->classroom_id] ?? $this->mapped('classroom', (string) $legacy->classroom_id);
            $studentId = $this->mapped('user', (string) $legacy->student_id);
            if (! $classroomId || ! $studentId) continue;

            $counts['roster_links'] += DB::table('classroom_student')->insertOrIgnore([
                'classroom_id' => $classroomId, 'student_id' => $studentId, 'assigned_by' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $classroom = Classroom::query()->with('course')->find($classroomId);
            if (! $classroom?->course) continue;

            $exists = CourseEnrollment::query()->where('course_id', $classroom->course_id)->where('student_id', $studentId)->exists();
            if (! $exists) {
                $active = $classroom->course->type === 'free';
                CourseEnrollment::query()->create([
                    'course_id' => $classroom->course_id, 'student_id' => $studentId,
                    'source' => 'legacy_import', 'status' => $active ? 'active' : 'pending_payment',
                    'granted_by' => null,
                    'source_reference' => 'sheykhan:classroom_student:'.$legacy->classroom_id.':'.$legacy->student_id,
                    'granted_at' => $active ? now() : null, 'expires_at' => null,
                ]);
                $counts['enrollments']++;
            }
        }
    }

    private function mapped(string $entity, string $legacyId): ?int
    {
        $id = DB::table('legacy_import_maps')
            ->where('source', self::SOURCE)->where('entity', $entity)->where('legacy_id', $legacyId)
            ->value('target_id');
        return $id === null ? null : (int) $id;
    }

    private function saveMap(string $entity, string $legacyId, int $targetId): void
    {
        DB::table('legacy_import_maps')->insertOrIgnore([
            'source' => self::SOURCE, 'entity' => $entity, 'legacy_id' => $legacyId, 'target_id' => $targetId,
            'imported_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function phone(string $value): string
    {
        $digits = strtr(trim($value), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        return preg_replace('/[\s-]+/u', '', $digits) ?? $digits;
    }
}
