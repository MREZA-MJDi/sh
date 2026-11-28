<?php

namespace App\Services\SpotPlayer;

use App\Models\SpotPlayerSetting;
use App\Services\Integrations\IntegrationException;
use App\Services\SpotPlayer\Contracts\IssuedSpotPlayerLicense;
use App\Services\SpotPlayer\Contracts\SpotPlayerLicenseCommand;
use Illuminate\Support\Facades\Http;

final class SpotPlayerLicenseApi
{
    public function issue(SpotPlayerSetting $settings, SpotPlayerLicenseCommand $command): IssuedSpotPlayerLicense
    {
        $result = Http::acceptJson()
            ->withHeaders(['$API' => $settings->api_key, '$LEVEL' => '-1'])
            ->withoutRedirecting()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://panel.spotplayer.ir/license/edit/', [
                'test' => $settings->mode !== 'production',
                'course' => [$command->externalCourseId],
                'name' => $command->studentName,
                'payload' => $command->idempotencyKey,
                'device' => $command->deviceLimits,
                'watermark' => ['texts' => [['text' => $command->watermarkText ?? $command->studentReference]]],
            ]);

        $payload = $result->json();
        $id = data_get($payload, '_id');
        $key = data_get($payload, 'key');
        $path = data_get($payload, 'url');
        if (! $result->successful() || ! is_string($id) || blank($id) || ! is_string($key) || blank($key)
            || ! is_string($path) || ! preg_match('~^/(?:[A-Za-z0-9_-]+/)+$~D', $path)) {
            throw new IntegrationException('صدور لایسنس از SpotPlayer تأیید نشد.');
        }

        return new IssuedSpotPlayerLicense($id, $key, 'https://dl.spotplayer.ir'.$path);
    }
}
