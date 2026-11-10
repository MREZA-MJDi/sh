<?php

namespace App\Services\Integrations;

use App\Models\IntegrationRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IntegrationOperationService
{
    /**
     * Persist an idempotent operation until the provider contract is installed.
     * No outbound request is performed by this service.
     */
    public function waitForContract(
        string $provider,
        string $action,
        ?Model $entity = null,
        array $safeRequestMeta = [],
        ?string $idempotencyKey = null,
    ): IntegrationRun {
        $key = $idempotencyKey ?: Str::uuid()->toString();

        return IntegrationRun::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'provider' => $provider,
                'action' => $action,
                'entity_type' => $entity?->getMorphClass(),
                'entity_id' => $entity?->getKey(),
                'status' => 'waiting_contract',
                'request_meta' => $safeRequestMeta,
                'correlation_id' => (string) Str::uuid(),
                'started_at' => now(),
            ]
        );
    }
}
