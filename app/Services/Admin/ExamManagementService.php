<?php

namespace App\Services\Admin;

use App\Models\Exam;
use App\Models\User;
use App\Services\Quiz24\Quiz24Service;
use Illuminate\Support\Facades\DB;

final class ExamManagementService
{
    public function __construct(private readonly Quiz24Service $quiz24) {}

    public function create(array $data, User $actor): Exam
    {
        $isQuiz24 = ($data['provider'] ?? 'internal') === 'quiz24';

        return DB::transaction(function () use ($data, $actor, $isQuiz24): Exam {
            $exam = Exam::create([
                ...$data,
                'created_by' => $actor->id,
                'integration_status' => $isQuiz24
                    ? (filled($data['external_id'] ?? null) ? 'awaiting_api_verification' : 'waiting_external_mapping')
                    : 'not_required',
                'integration_note' => $isQuiz24
                    ? 'آزمون محلی ثبت شد؛ ایجاد یا تأیید آزمون در Quiz24 به قرارداد وب‌سرویس نیاز دارد.'
                    : null,
            ]);

            if ($isQuiz24) {
                $this->quiz24->requestExamProvisioning($exam, $actor);
            }

            return $exam->refresh();
        });
    }

    public function update(Exam $exam, array $data, User $actor): Exam
    {
        $isQuiz24 = ($data['provider'] ?? 'internal') === 'quiz24';

        return DB::transaction(function () use ($exam, $data, $actor, $isQuiz24): Exam {
            $exam->update([
                ...$data,
                'integration_status' => $isQuiz24
                    ? (filled($data['external_id'] ?? null) ? 'awaiting_api_verification' : 'waiting_external_mapping')
                    : 'not_required',
                'integration_note' => $isQuiz24
                    ? 'اطلاعات آزمون محلی ذخیره شد؛ اعتبار شناسه و همگام‌سازی تا تأیید API انجام نمی‌شود.'
                    : null,
            ]);

            if ($isQuiz24) {
                $this->quiz24->requestExamProvisioning($exam->refresh(), $actor);
            }

            return $exam->refresh();
        });
    }
}
