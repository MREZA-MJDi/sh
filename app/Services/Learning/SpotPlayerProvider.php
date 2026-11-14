<?php

namespace App\Services\Learning;

use Abdal\SpotPlayer\SpotPlayer; // composer require abdal/spotplayer-php (پکیج جامعه؛ نام کلاس را چک کن)
use App\Exceptions\ContractPendingException;

/**
 * فقط بعد از تأیید مستندات/Sandbox رسمی SpotPlayer در LearningServiceProvider به‌جای
 * PendingContractProvider bind شود. اول با 'test' => true امتحان کن.
 * در config/services.php:  'spotplayer' => ['key' => env('SPOTPLAYER_API_KEY')]
 */
class SpotPlayerProvider implements LearningProvider
{
    public function issueLicense(string $externalCourseId, string $holderName, string $watermark): array
    {
        $r = (new SpotPlayer(config('services.spotplayer.key')))->createLicense([
            'course' => [$externalCourseId],
            'name' => $holderName,
            'watermark' => ['texts' => [['text' => $watermark]]],
            // 'test' => true,
        ]);

        return ['id' => $r['_id'], 'key' => $r['key']];
    }

    public function uploadVideo(string $absolutePath, string $title): string
    {
        throw new ContractPendingException('ویدیو را مستقیم در پنل SpotPlayer آپلود کنید و شناسه‌اش را در درس بنویسید.');
    }
}
