<?php

namespace App\Services\SpotPlayer;

use App\Services\Integrations\IntegrationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SpotPlayerClient
{
    private const BASE_URL = 'https://panel.spotplayer.ir';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $mode = 'test',
    ) {
        if (trim($this->apiKey) === '') {
            throw new IntegrationException('کلید API اسپات پلیر تنظیم نشده است.');
        }
    }

    public function courses(): array
    {
        return $this->request('GET', '/course/?p=0');
    }

    public function course(string $courseId): array
    {
        return $this->request('GET', '/course/edit/'.rawurlencode($courseId));
    }

    public function licenses(): array
    {
        return $this->request('GET', '/license/?p=0');
    }

    public function license(string $licenseId): array
    {
        return $this->request('GET', '/license/edit/'.rawurlencode($licenseId));
    }

    /**
     * SpotPlayer license endpoint contract used by the Laravel wrapper:
     * POST /license/edit/ with course, name, watermark, device and test.
     *
     * Course records themselves are created in the SpotPlayer teacher panel;
     * this API integration lists/maps them and issues licenses for mapped IDs.
     */
    public function createLicense(
        string $name,
        array $courseIds,
        array $watermark,
        array $device,
        string $payload,
    ): array {
        return $this->request('POST', '/license/edit/', [
            'test' => $this->mode === 'test',
            'name' => $name,
            'course' => array_values($courseIds),
            'watermark' => $watermark,
            'device' => $device,
            'payload' => $payload,
        ]);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $url = self::BASE_URL.$path;

        try {
            $request = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    '$API' => $this->apiKey,
                    '$LEVEL' => '-1',
                ])
                ->connectTimeout(8)
                ->timeout(25)
                ->withOptions(['verify' => true]);

            $response = $method === 'GET'
                ? $request->get($url)
                : $request->post($url, $payload ?? []);
        } catch (ConnectionException $exception) {
            throw new IntegrationException('ارتباط با سرور اسپات پلیر برقرار نشد.', previous: $exception);
        }

        if ($response->failed()) {
            throw new IntegrationException(
                'درخواست اسپات پلیر ناموفق بود. کد پاسخ: '.$response->status()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new IntegrationException('پاسخ اسپات پلیر JSON معتبر نیست.');
        }

        $providerError = data_get($data, 'ex.msg');

        if (is_string($providerError) && $providerError !== '') {
            throw new IntegrationException('اسپات پلیر درخواست را رد کرد: '.mb_substr($providerError, 0, 300));
        }

        return $data;
    }
}
