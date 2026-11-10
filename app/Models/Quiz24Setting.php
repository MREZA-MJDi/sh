<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz24Setting extends Model
{
    protected $fillable = ['account_name', 'api_base_url', 'api_token', 'is_enabled'];

    protected function casts(): array
    {
        return ['api_token' => 'encrypted', 'is_enabled' => 'boolean'];
    }
}
