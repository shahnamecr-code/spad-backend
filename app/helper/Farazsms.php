<?php

namespace App\helper;

use GuzzleHttp\Client;
use Tzsk\Sms\Contracts\Driver;

class Farazsms extends Driver
{
    protected Client $client;

    protected $template;


    protected function boot(): void
    {
        $this->client = new Client();
    }

    public function template($template, $parameters)
    {
        $this->template = $template;
        $this->parameters = $parameters;
    }

    public function send()
    {
        $response = collect();

        foreach ($this->recipients as $recipient) {
            $query_string = '?username=' . data_get($this->settings, 'username');
            $query_string .= '&password=' . data_get($this->settings, 'password');
            $query_string .= '&from=' . data_get($this->settings, 'from');
            $query_string .= '&to=' . json_encode([$recipient]);
            $query_string .= '&pattern_code=' . $this->template;
            $query_string .= '&input_data=' . urlencode(json_encode($this->parameters));

            $result = $this->client->request(
                'POST',
                data_get($this->settings, 'url') . $query_string,
                [
                    'input_data' => $this->parameters
                ]
            );
            $response->put($recipient, json_decode((string)$result->getBody()));
        }

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }

    protected function payload($recipient): array
    {
        return [
            'form_params' => [
                'input_data' => urlencode(json_encode($this->parameters))
            ]
        ];
    }
}
