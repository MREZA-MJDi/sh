<?php

namespace App\Services\SpotPlayer\Contracts;

use App\Services\Integrations\Contracts\IntegrationHealth;

interface SpotPlayerGateway
{
    public function health(): IntegrationHealth;

    /** @return array<int, array{id:string,title:string,status?:string}> */
    public function listCourses(): array;

    public function issueLicense(SpotPlayerLicenseCommand $command): IssuedSpotPlayerLicense;

    public function revokeLicense(string $externalLicenseId, string $reason, string $idempotencyKey): void;
}
