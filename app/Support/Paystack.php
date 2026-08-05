<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Paystack
{
    protected static function client()
    {
        return Http::withToken((string) config('services.paystack.secret'))
            ->acceptJson()
            ->baseUrl('https://api.paystack.co')
            ->timeout(30);
    }

    public static function configured(): bool
    {
        return ! empty(config('services.paystack.secret'));
    }

    /**
     * Initialize a transaction. Returns the decoded Paystack response
     * (['status' => bool, 'data' => ['authorization_url' => ..., ...]]).
     */
    public static function initialize(array $payload): array
    {
        return static::client()->post('/transaction/initialize', $payload)->json() ?? ['status' => false];
    }

    public static function verify(string $reference): array
    {
        return static::client()->get('/transaction/verify/'.$reference)->json() ?? ['status' => false];
    }
}
