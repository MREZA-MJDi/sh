<?php

namespace App\Services\Quiz24;

use App\Models\Exam;
use App\Models\IntegrationRun;
use App\Models\Quiz24UnmatchedParticipant;
use App\Models\User;
use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Integrations\IntegrationOperationService;
use App\Services\Quiz24\Contracts\Quiz24Gateway;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use Illuminate\Support\Str;

class Quiz24Service
{
    public function __construct(
        private readonly Quiz24Gateway $gateway,
        private readonly IntegrationOperationService $operations,
        private readonly Quiz24ResultImporter $importer,
    ) {}

    public function health(): IntegrationHealth
    {
        return $this->gateway->health();
    }

    public function requestExamCatalogSync(User $requestedBy): IntegrationRun
    {
        return $this->operations->waitForContract(
            provider: 'quiz24',
            action: 'sync_exam_catalog',
            safeRequestMeta: ['requested_by' => $requestedBy->id],
            idempotencyKey: 'quiz24:exam-catalog:'.now()->format('Ymd'),
        );
    }

    public function requestExamProvisioning(Exam $exam, User $requestedBy): IntegrationRun
    {
        if ($exam->provider !== 'quiz24') {
            throw new IntegrationException('فقط آزمون‌های ارائه‌شده توسط Quiz24 قابلیت اتصال به این سرویس را دارند.');
        }

        $hasExternalId = filled($exam->external_id);

        return $this->operations->waitForContract(
            provider: 'quiz24',
            action: $hasExternalId ? 'verify_exam_mapping' : 'create_exam',
            entity: $exam,
            safeRequestMeta: [
                'local_exam_id' => $exam->id,
                'requested_external_exam_id' => $exam->external_id,
                'requested_by' => $requestedBy->id,
            ],
            idempotencyKey: 'quiz24:exam-provision:'.$exam->id.':'.sha1((string) $exam->external_id),
        );
    }

    public function requestResultsSync(Exam $exam, User $requestedBy): IntegrationRun
    {
        if ($exam->provider !== 'quiz24' || blank($exam->external_id)) {
            throw new IntegrationException('آزمون به Quiz24 متصل نشده یا شناسه خارجی ندارد.');
        }

        if ($exam->status !== 'published' && $exam->status !== 'closed') {
            throw new IntegrationException('نتایج فقط برای آزمون منتشرشده یا بسته‌شده قابل دریافت است.');
        }

        return $this->operations->waitForContract(
            provider: 'quiz24',
            action: 'sync_exam_results',
            entity: $exam,
            safeRequestMeta: [
                'external_exam_id' => $exam->external_id,
                'requested_by' => $requestedBy->id,
            ],
            idempotencyKey: 'quiz24:results:'.$exam->id.':'.now()->format('YmdHi'),
        );
    }

    public function requestStudentLaunch(Exam $exam, User $student, string $returnPath): IntegrationRun
    {
        if (! $student->is_active || ! $student->hasRole('student')) {
            throw new IntegrationException('ورود به آزمون فقط برای دانش‌آموز فعال مجاز است.');
        }

        if ($exam->provider !== 'quiz24' || $exam->status !== 'published' || blank($exam->external_id)) {
            throw new IntegrationException('آزمون Quiz24 منتشرشده و دارای شناسه خارجی معتبر نیست.');
        }

        if (blank($student->external_student_id)) {
            throw new IntegrationException('شناسه پایدار دانش‌آموز برای ورود یکپارچه Quiz24 ثبت نشده است.');
        }

        if (! str_starts_with($returnPath, '/') ||
            str_starts_with($returnPath, '//') ||
            str_contains($returnPath, '://') ||
            str_contains($returnPath, '\\\\')) {
            throw new IntegrationException('مسیر بازگشت آزمون معتبر نیست.');
        }

        return $this->operations->waitForContract(
            provider: 'quiz24',
            action: 'create_student_launch_url',
            entity: $exam,
            safeRequestMeta: [
                'local_exam_id' => $exam->id,
                'external_exam_id' => $exam->external_id,
                'student_id' => $student->id,
                'student_external_reference' => $student->external_student_id,
                'return_path' => $returnPath,
            ],
            idempotencyKey: 'quiz24:launch:'.$exam->id.':'.$student->id.':'.Str::uuid(),
        );
    }

    /** @param iterable<Quiz24ResultData> $results */
    public function importResults(Exam $exam, iterable $results): array
    {
        return $this->importer->import($exam, $results);
    }

    public function resolveParticipant(
        Quiz24UnmatchedParticipant $participant,
        User $student,
        User $resolvedBy
    ): void {
        $this->importer->resolveManually($participant, $student, $resolvedBy);
    }
}
