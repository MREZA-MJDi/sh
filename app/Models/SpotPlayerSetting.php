<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpotPlayerSetting extends Model
{
    protected $fillable = [
        'account_id',
        'api_key',
        'max_devices',
        'watermark_enabled',
        'auto_license_enabled',
        'mode',
        'api_enabled',
        'contract_version',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'max_devices' => 'integer',
            'watermark_enabled' => 'boolean',
            'auto_license_enabled' => 'boolean',
            'api_enabled' => 'boolean',
        ];
    }
}
