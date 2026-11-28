<?php

namespace App\Services;

use App\Models\SpadAiAssistantSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SpadOpenAi
{
    public static function settings(): ?SpadAiAssistantSetting
    {
        return Schema::hasTable('spad_ai_assistant_settings') ? SpadAiAssistantSetting::activeSettings() : null;
    }

    public static function key(?SpadAiAssistantSetting $settings): ?string
    {
        return $settings && $settings->enabled ? $settings->apiKey() : null;
    }

    public static function vision(array $content, int $maxTokens = 4000): array
    {
        $settings = self::settings();
        $key = self::key($settings);
        if (!$key) {
            throw new RuntimeException('openai-not-enabled');
        }

        $response = Http::withToken($key)->acceptJson()->withOptions(['connect_timeout' => 8])->timeout(105)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $settings->model ?: 'gpt-4.1-mini',
                'input' => [['role' => 'user', 'content' => $content]],
                'text' => ['format' => ['type' => 'json_object']],
                'store' => false,
                'max_output_tokens' => $maxTokens,
            ]);

        if (!$response->successful() || $response->json('status') === 'incomplete') {
            throw new RuntimeException('openai-vision-http-' . $response->status());
        }

        $answer = '';
        foreach ((array) $response->json('output', []) as $message) {
            foreach (($message['content'] ?? []) as $part) {
                if (($part['type'] ?? '') === 'output_text' && is_string($part['text'] ?? null)) {
                    $answer .= $part['text'];
                }
            }
        }
        $result = json_decode($answer, true);
        if (!is_array($result)) {
            throw new RuntimeException('openai-vision-invalid-response');
        }
        return $result;
    }

    public static function image(string $jpeg, string $detail = 'high'): array
    {
        return [
            'type' => 'input_image',
            'image_url' => 'data:image/jpeg;base64,' . base64_encode($jpeg),
            'detail' => $detail,
        ];
    }
}
