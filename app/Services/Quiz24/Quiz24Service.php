<?php

namespace App\Services\Quiz24;

use App\Models\Exam;
use App\Models\IntegrationRun;
use App\Models\User;
use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationOperationService;
use App\Services\Quiz24\Contracts\Quiz24Gateway;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use Illuminate\Support\Facades\DB;

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

    public function requestResultsSync(Exam $exam, User $requestedBy): IntegrationRun
    {
        abort_unless($exam->provider === 'quiz24' && filled($exam->external_id), 422, 'آزمون به Quiz24 متصل نشده است.');

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

    /**
     * Adapter workers call this with normalized DTOs after official API is wired.
     *
     * @param iterable<Quiz24ResultData> $results
     */
    public function importResults(Exam $exam, iterable $results): array
    {
        return $this->importer->import($exam, $results);
    }
}
