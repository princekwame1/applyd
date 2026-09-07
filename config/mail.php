<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        /*
         * Mailgun's HTTP API, not SMTP: one HTTPS request per message, so a
         * blocked outbound port 465/587 (common on shared hosting) can't stop
         * mail, and a rejection comes back as a readable error instead of an
         * SMTP code. Credentials live in config/services.php ('mailgun').
         */
        'mailgun' => [
            'transport' => 'mailgun',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'mailgun',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Mail Instead of Sending Inline
    |--------------------------------------------------------------------------
    |
    | Off: an email is handed to Mailgun during the request that caused it, and
    | the log row is 'sent' or 'failed' by the time the page renders. This is
    | the setting the site runs on — the queue existed to pace the old cPanel
    | relay's hourly cap, and Mailgun's API has no cap worth pacing at this
    | volume, so the queue was buying a worker, a cron line and a delay for
    | nothing.
    |
    | On (with QUEUE_CONNECTION not 'sync'): back to queued delivery, throttled
    | by 'hourly_limit' below and drained by the worker in routes/console.php.
    | Turn it on if a broadcast ever gets big enough that sending it inside one
    | HTTP request would hit the server's max_execution_time — a few hundred
    | recipients is where that starts to matter.
    |
    */

    'queue_enabled' => filter_var(env('MAIL_QUEUE_ENABLED', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Outgoing Rate Limit
    |--------------------------------------------------------------------------
    |
    | Only consulted while 'queue_enabled' is on — inline sends never touch it.
    |
    | Mailgun rate-limits by plan and answers anything past it with 429, so
    | mail is handed to the queue and released at this rate instead of being
    | pushed out inline. Mailgun's ceiling is far higher than the cPanel relay
    | this replaced, but a trial account is capped hard (a few hundred a day)
    | and a new sending domain should be warmed up rather than opened with a
    | 500-recipient blast — so the throttle stays, set BELOW the plan's limit.
    | The cost of being under is that a broadcast takes longer; the cost of
    | being over is throttled, then bounced, mail.
    |
    | 0 disables the throttle entirely (still queued, just never held back).
    |
    */

    'hourly_limit' => (int) env('MAIL_HOURLY_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Mail Queues
    |--------------------------------------------------------------------------
    |
    | Transactional mail (a confirmation, a student's login) rides the first
    | queue and a bulk broadcast the second, so a 500-recipient blast can never
    | park itself in front of someone waiting to be let into the portal. The
    | worker must be given both, in this order — see .env.example.
    |
    | 'retry_hours' bounds how long a message may sit being retried before it
    | is written off as failed.
    |
    */

    'queues' => [
        'priority' => env('MAIL_QUEUE', 'emails'),
        'bulk' => env('MAIL_QUEUE_BULK', 'emails-bulk'),
    ],

    'retry_hours' => (int) env('MAIL_RETRY_HOURS', 24),

];
