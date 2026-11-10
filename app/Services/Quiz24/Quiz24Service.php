<?php

namespace App\Services\Quiz24;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\IntegrationRun;
use App\Models\Quiz24Setting;
use App\Models\Quiz24UnmatchedParticipant;
use App\Models\User;
use App\Services\Integrations\IntegrationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Quiz24Service
{
    public function isConfigured(): bool
    {
        $settings = Quiz24Setting::query()->first();

        return (bool) (
            $settings
            && $settings->is_enabled
            && filled($settings->api_base_url)
            && filled($settings->api_token)
            && filled($settings->api_token_header)
            && filled($settings->exams_endpoint)
            && filled($settings->results_endpoint)
        );
    }

    public function importExams(): Collection
    {
        $settings = $this->settings();
        $this->requireExamMapping($settings);

        $run = $this->startRun('import_exams');
        try {
            $response = $this->client($settings)->get($settings->exams_endpoint);
            $items = $this->collectionAtPath($response, $settings->exams_collection_path);

            $imported = collect();

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $externalId = data_get($item, $settings->exam_id_path);
                $title = data_get($item, $settings->exam_title_path);

                if (! is_scalar($externalId) || ! is_scalar($title) || trim((string) $externalId) === '' || trim((string) $title) === '') {
                    continue;
                }

                $exam = Exam::firstOrNew([
                    'provider' => 'quiz24',
                    'external_id' => (string) $externalId,
                ]);

                if (! $exam->exists) {
                    $exam->fill([
                        'title' => Str::limit(trim((string) $title), 180, ''),
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ]);
                    $exam->save();
                }

                $imported->push($exam);
            }

            $run->update([
                'status' => 'succeeded',
                'response_meta' => ['imported_count' => $imported->count()],
                'completed_at' => now(),
            ]);

            return $imported;
        } catch (Throwable $exception) {
            $this->failRun($run, $exception);
            throw $exception instanceof IntegrationException
                ? $exception
                : new IntegrationException('دریافت فهرست آزمون‌های Quiz24 انجام نشد.', previous: $exception);
        }
    }

    public function syncResults(Exam $exam): array
    {
        if ($exam->provider !== 'quiz24' || blank($exam->external_id)) {
            throw new IntegrationException('این آزمون به یک شناسه آزمون معتبر Quiz24 متصل نیست.');
        }

        $settings = $this->settings();
        $this->requireResultMapping($settings);

        if (! str_contains($settings->results_endpoint, '{exam_id}')) {
            throw new IntegrationException('مسیر نتایج Quiz24 باید شامل جای‌نگهدار {exam_id} باشد.');
        }

        $endpoint = str_replace('{exam_id}', rawurlencode($exam->external_id), $settings->results_endpoint);
        $run = $this->startRun('sync_results', Exam::class, $exam->id);

        try {
            $response = $this->client($settings)->get($endpoint);
            $items = $this->collectionAtPath($response, $settings->results_collection_path);

            $matched = 0;
            $unmatched = 0;
            $invalid = 0;

            DB::transaction(function () use (
                $items,
                $settings,
                $exam,
                &$matched,
                &$unmatched,
                &$invalid
            ) {
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        $invalid++;
                        continue;
                    }

                    $identifier = data_get($item, $settings->result_student_identifier_path);
                    $score = data_get($item, $settings->result_score_path);
                    $maxScore = filled($settings->result_max_score_path)
                        ? data_get($item, $settings->result_max_score_path, 20)
                        : 20;

                    if (! is_scalar($identifier) || trim((string) $identifier) === '' ||
                        ! is_numeric($score) || ! is_numeric($maxScore) ||
                        (float) $score < 0 || (float) $maxScore <= 0 ||
                        (float) $score > (float) $maxScore) {
                        $invalid++;
                        continue;
                    }

                    $externalIdentifier = $this->normalizeIdentifier((string) $identifier);
                    $student = User::query()
                        ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                        ->where(function ($query) use ($externalIdentifier) {
                            $query->where('external_student_id', $externalIdentifier)
                                ->orWhere('phone', $externalIdentifier);
                        })
                        ->first();

                    if (! $student) {
                        $displayName = filled($settings->result_display_name_path)
                            ? data_get($item, $settings->result_display_name_path)
                            : null;

                        Quiz24UnmatchedParticipant::updateOrCreate(
                            [
                                'exam_id' => $exam->id,
                                'external_identifier' => $externalIdentifier,
                            ],
                            [
                                'display_name' => is_scalar($displayName) ? Str::limit((string) $displayName, 190, '') : null,
                                'score' => $score,
                                'max_score' => $maxScore,
                                'status' => 'unmatched',
                                'source_data' => $item,
                                'first_seen_at' => now(),
                            ]
                        );

                        $unmatched++;
                        continue;
                    }

                    ExamResult::updateOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $student->id],
                        [
                            'score' => $score,
                            'max_score' => $maxScore,
                            'source_data' => $item,
                            'synced_at' => now(),
                        ]
                    );

                    Quiz24UnmatchedParticipant::where('exam_id', $exam->id)
                        ->where('external_identifier', $externalIdentifier)
                        ->delete();

                    $matched++;
                }
            });

            $summary = [
                'matched' => $matched,
                'unmatched' => $unmatched,
                'invalid' => $invalid,
            ];

            $run->update([
                'status' => 'succeeded',
                'response_meta' => $summary,
                'completed_at' => now(),
            ]);

            return $summary;
        } catch (Throwable $exception) {
            $this->failRun($run, $exception);

            throw $exception instanceof IntegrationException
                ? $exception
                : new IntegrationException('همگام‌سازی نتایج Quiz24 انجام نشد.', previous: $exception);
        }
    }

    private function settings(): Quiz24Setting
    {
        $settings = Quiz24Setting::query()->first();

        if (! $settings) {
            throw new IntegrationException('تنظیمات Quiz24 ثبت نشده است.');
        }

        return $settings;
    }

    private function client(Quiz24Setting $settings): Quiz24Client
    {
        return new Quiz24Client($settings);
    }

    private function collectionAtPath(array $response, ?string $path): array
    {
        $items = filled($path) ? data_get($response, $path) : $response;

        if (! is_array($items)) {
            throw new IntegrationException('مسیر آرایه‌ای پاسخ در تنظیمات Quiz24 با ساختار واقعی پاسخ مطابقت ندارد.');
        }

        return array_is_list($items) ? $items : array_values($items);
    }

    private function requireExamMapping(Quiz24Setting $settings): void
    {
        foreach (['exams_endpoint', 'exam_id_path', 'exam_title_path'] as $field) {
            if (blank($settings->{$field})) {
                throw new IntegrationException('تنظیم نگاشت فهرست آزمون‌های Quiz24 ناقص است: '.$field);
            }
        }
    }

    private function requireResultMapping(Quiz24Setting $settings): void
    {
        foreach (['results_endpoint', 'result_student_identifier_path', 'result_score_path'] as $field) {
            if (blank($settings->{$field})) {
                throw new IntegrationException('تنظیم نگاشت نتایج Quiz24 ناقص است: '.$field);
            }
        }
    }

    private function startRun(string $action, ?string $entityType = null, ?int $entityId = null): IntegrationRun
    {
        return IntegrationRun::create([
            'provider' => 'quiz24',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    private function failRun(IntegrationRun $run, Throwable $exception): void
    {
        $run->update([
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 1000),
            'completed_at' => now(),
        ]);
    }

    private function normalizeIdentifier(string $value): string
    {
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-]/u', '', $value) ?? $value;
    }
}
