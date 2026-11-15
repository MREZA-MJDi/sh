<?php

namespace App\Services\Learning;

use App\Exceptions\ContractPendingException;

/**
 * Adapter پیش‌فرض: تا زمانی که مستندات و Sandbox رسمی SpotPlayer نرسیده،
 * هیچ درخواست ساختگی ارسال نمی‌شود. بعد از تحویل، یک کلاس واقعی بنویس که
 * LearningProvider را پیاده کند و در LearningServiceProvider جایگزین این کن.
 */
class PendingContractProvider implements LearningProvider
{
    public function issueLicense(string $externalCourseId, string $holderName, string $watermark): array
    {
        throw new ContractPendingException('قرارداد API سرویس‌دهنده هنوز تأیید نشده است.');
    }

    public function uploadVideo(string $absolutePath, string $title): string
    {
        throw new ContractPendingException('قرارداد آپلود سرویس‌دهنده هنوز تأیید نشده است.');
    }
}
