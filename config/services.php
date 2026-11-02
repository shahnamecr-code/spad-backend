<?php

return [
    'spad_shahkar' => [
        'key' => env('SPAD_SHAHKAR_NEXT_PAY_KEY'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'spad_crm' => [
        'sms_enabled' => env('SPAD_CRM_SMS_ENABLED', false),
        'sms_key' => env('SMSIR_API_KEY', ''),
        'sms_line' => env('SPAD_CRM_SMS_LINE', ''),
        'voice_enabled' => env('SPAD_CRM_VOICE_ENABLED', false),
        'voice_key' => env('SPAD_CRM_VOICE_API_KEY', ''),
    ],
];
