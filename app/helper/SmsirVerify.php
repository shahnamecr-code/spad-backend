<?php

namespace App\helper;

use GuzzleHttp\Client;
use RuntimeException;
use Tzsk\Sms\Contracts\Driver;

class SmsirVerify extends Driver
{
    protected Client $client;

    protected $template;

    protected array $parameters = [];

    protected function boot(): void
    {
        $this->client = new Client([
            'timeout' => (float) data_get($this->settings, 'timeout', 10),
            'connect_timeout' => (float) data_get($this->settings, 'connect_timeout', 5),
        ]);
    }

    public function template($template, $parameters)
    {
        $this->template = $template;
        $this->parameters = is_array($parameters) ? $parameters : [];

        return $this;
    }

    public function send()
    {
        $apiKey = trim((string) data_get($this->settings, 'api_key'));
        $url = rtrim((string) data_get($this->settings, 'url', 'https://api.sms.ir/v1/send/verify'), '/');
        $templateId = (int) $this->template;

        if ($apiKey === '') {
            throw new RuntimeException('SMS.ir API key is not configured.');
        }

        if ($templateId <= 0) {
            throw new RuntimeException('SMS.ir TemplateId is not configured.');
        }

        $parameters = [];
        foreach ($this->parameters as $name => $value) {
            $parameters[] = [
                'name' => (string) $name,
                'value' => (string) $value,
            ];
        }

        $response = collect();

        foreach ($this->recipients as $recipient) {
            $result = $this->client->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $apiKey,
                ],
                'json' => [
                    'Mobile' => (string) $recipient,
                    'TemplateId' => $templateId,
                    'Parameters' => $parameters,
                ],
                'http_errors' => false,
            ]);

            $status = $result->getStatusCode();
            $body = json_decode((string) $result->getBody());

            if ($status < 200 || $status >= 300) {
                $message = is_object($body) && isset($body->message)
                    ? (string) $body->message
                    : 'SMS.ir request failed with HTTP status ' . $status;

                throw new RuntimeException($message);
            }

            $response->put($recipient, $body);
        }

        return (count($this->recipients) === 1) ? $response->first() : $response;
    }

    protected function payload($recipient): array
    {
        return [];
    }
}
