<?php

namespace App\Services\Quiz24;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Quiz24StudentMapping;
use App\Models\Quiz24UnmatchedParticipant;
use App\Models\User;
use App\Services\Integrations\IntegrationException;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use Illuminate\Support\Facades\DB;

class Quiz24ResultImporter
{
    /**
     * @param iterable<Quiz24ResultData> $results
     * @return array{matched:int,unmatched:int,invalid:int}
     */
    public function import(Exam $exam, iterable $results): array
    {
        if ($exam->provider !== 'quiz24' || blank($exam->external_id)) {
            throw new IntegrationException('آزمون به شناسه معتبر Quiz24 متصل نیست.');
        }

        $summary = ['matched' => 0, 'unmatched' => 0, 'invalid' => 0];

        DB::transaction(function () use ($exam, $results, &$summary): void {
            foreach ($results as $result) {
                if (! $result instanceof Quiz24ResultData ||
                    trim($result->studentIdentifier) === '' ||
                    $result->score < 0 ||
                    $result->maxScore <= 0 ||
                    $result->score > $result->maxScore) {
                    $summary['invalid']++;
                    continue;
                }

                $identifier = $this->normalizeIdentifier($result->studentIdentifier);

                $studentId = Quiz24StudentMapping::query()
                    ->where('provider', 'quiz24')
                    ->where('external_identifier', $identifier)
                    ->value('student_id');

                $student = $studentId
                    ? User::query()->whereKey($studentId)->where('is_active', true)->first()
                    : User::query()
                        ->where('is_active', true)
                        ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                        ->where(function ($query) use ($identifier) {
                            $phoneIdentifier = $this->normalizePhone($identifier);

                            $query->where('external_student_id', $identifier)
                                ->orWhere('phone', $phoneIdentifier)
                                ->orWhere('phone', $identifier);
                        })
                        ->first();

                $source = [
                    'provider_result_id' => $result->resultId,
                    'external_student_identifier' => $identifier,
                    'submitted_at' => $result->submittedAt,
                ];

                if (! $student) {
                    Quiz24UnmatchedParticipant::updateOrCreate(
                        ['exam_id' => $exam->id, 'external_identifier' => $identifier],
                        [
                            'display_name' => $result->studentName,
                            'score' => $result->score,
                            'max_score' => $result->maxScore,
                            'status' => 'unmatched',
                            'source_data' => $source,
                            'first_seen_at' => now(),
                        ]
                    );

                    $summary['unmatched']++;
                    continue;
                }

                ExamResult::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $student->id],
                    [
                        'score' => $result->score,
                        'max_score' => $result->maxScore,
                        'source_data' => $source,
                        'synced_at' => now(),
                    ]
                );

                Quiz24UnmatchedParticipant::query()
                    ->where('exam_id', $exam->id)
                    ->where('external_identifier', $identifier)
                    ->delete();

                $summary['matched']++;
            }
        });

        return $summary;
    }

    public function resolveManually(Quiz24UnmatchedParticipant $participant, User $student, User $resolvedBy): void
    {
        if (! $student->is_active || ! $student->hasRole('student')) {
            throw new IntegrationException('فقط دانش‌آموز فعال قابل اتصال به نتیجه آزمون است.');
        }

        if ($participant->status !== 'unmatched') {
            throw new IntegrationException('این شرکت‌کننده قبلاً تعیین تکلیف شده است.');
        }

        DB::transaction(function () use ($participant, $student, $resolvedBy): void {
            $participant = Quiz24UnmatchedParticipant::query()->lockForUpdate()->findOrFail($participant->id);

            Quiz24StudentMapping::updateOrCreate(
                [
                    'provider' => 'quiz24',
                    'external_identifier' => $participant->external_identifier,
                ],
                [
                    'student_id' => $student->id,
                    'created_by' => $resolvedBy->id,
                ]
            );

            ExamResult::updateOrCreate(
                ['exam_id' => $participant->exam_id, 'student_id' => $student->id],
                [
                    'score' => $participant->score,
                    'max_score' => $participant->max_score,
                    'source_data' => [
                        'resolved_from_unmatched_participant_id' => $participant->id,
                        'external_student_identifier' => $participant->external_identifier,
                    ],
                    'synced_at' => now(),
                ]
            );

            $participant->update([
                'status' => 'matched',
                'resolved_user_id' => $student->id,
            ]);
        });
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = strtr(trim($identifier), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        // Preserve meaningful provider ID characters such as hyphens.
        return preg_replace('/\\s+/u', '', $identifier) ?? $identifier;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\\s\\-()]+/u', '', $phone) ?? $phone;
    }
}
