<?php

return [

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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fiscalization' => [
        'url' => env('FISCALIZATION_API_URL', 'https://elif12.2rmlab.com/live/api/sales.php'),
        'server_config' => env('FISCALIZATION_SERVER_CONFIG', '{
            "Url_API": "https://elif12.2rmlab.com/live/api",
            "DB_Config": "elif_config",
            "Company_DB_Name": "Elif_001_1202260_07-2024",
            "HardwareId": "cfe8a423409129b0c36b418c71385eec",
            "UserInfo": {
                "user_id": 8001950,
                "username": "fiscaluser",
                "password": null,
                "token": "6c3b1ef34f5e69711e3d52bc8a78ef4811039acdb383a84b8a6d17757e904734a318006cb0069ada6f132400be18701493e487ced6b7f6dfdb1da61a0e21f929"
            }
        }'),
        'business_unit' => env('FISCALIZATION_BUSINESS_UNIT', 'li519qp911'),
        'cash_register' => env('FISCALIZATION_CASH_REGISTER', 'fk681zu051'),
        'software' => env('FISCALIZATION_SOFTWARE', 'dx582kn875'),
        'verification_base_url' => env('FISCALIZATION_VERIFICATION_URL', 'https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify'),
    ],

];
