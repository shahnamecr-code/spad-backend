<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpadAiAssistantSetting extends Model
{
    protected $table = 'spad_ai_assistant_settings';
    protected $guarded = [];
    protected $hidden = ['api_key_encrypted'];
    protected $casts = [
        'enabled' => 'boolean',
        'daily_limit' => 'integer',
        'max_output_tokens' => 'integer',
    ];

    public static function activeSettings()
    {
        return static::query()->first();
    }

    public function apiKey(): ?string
    {
        if (!empty($this->api_key_encrypted)) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->api_key_encrypted);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return (string) (config('services.openai.api_key') ?: env('OPENAI_API_KEY') ?: '') ?: null;
    }
}
