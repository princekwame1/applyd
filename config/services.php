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

    /*
    | Mailgun — the transactional mail relay. 'domain' is the *sending* domain
    | as Mailgun spells it (the one whose DNS is verified), and 'secret' is the
    | API key's secret, which Mailgun shows once when the key is created — the
    | dashboard lists only the key id afterwards, so a lost secret is replaced,
    | never recovered. EU accounts must set MAILGUN_ENDPOINT to the EU host:
    | a key issued in one region is not valid in the other.
    */
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    /*
    | The sibling learning portal (applyd-portal): its own app, on this same
    | database, sharing these accounts. Students sign in there, so the
    | credentials this app issues have to link to it.
    */
    'portal' => [
        /*
        | Defaulted rather than left null on purpose. With no value, every
        | student link fell back to this site's /login — a door a `student`
        | role cannot open — and nothing said so until someone clicked it.
        | The portal address is known, so it belongs here; PORTAL_URL is for
        | pointing a staging build somewhere else.
        */
        'url' => env('PORTAL_URL', 'https://sts.applydacademy.com'),
    ],

    'paystack' => [
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'public' => env('PAYSTACK_PUBLIC_KEY'),
        'currency' => env('PAYSTACK_CURRENCY', 'GHS'),

        /*
        | Passing Paystack's transaction charge on to the payer. These are
        | Paystack's numbers, not ours — they differ by country and payment
        | method and Paystack changes them, so confirm against your dashboard
        | rather than trusting the defaults. `cap` is the most it will ever
        | take on one transaction (blank = uncapped).
        |
        | Off by default: switching it on changes what every customer is
        | charged, which should be a deliberate act, not something that
        | arrives with a deploy.
        */
        'fee' => [
            'pass_on' => env('PAYSTACK_PASS_ON_FEE', false),
            'percent' => env('PAYSTACK_FEE_PERCENT', 1.95),
            'fixed' => env('PAYSTACK_FEE_FIXED', 0),
            'cap' => env('PAYSTACK_FEE_CAP'),
        ],
    ],

    'mnotify' => [
        'api_key' => env('MNOTIFY_API_KEY'),
        'sender_id' => env('MNOTIFY_SENDER_ID', 'Applyd'),
    ],

];
