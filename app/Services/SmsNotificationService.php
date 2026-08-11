<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    protected ?string $apiKey;
    protected ?string $senderId;
    protected string $apiUrl = 'https://api.mnotify.com/api/sms/quick';

    public function __construct()
    {
        // Nullable: the service degrades to a logged failure when MNotify is
        // not configured, so a missing key must not blow up the constructor.
        $this->apiKey = config('services.mnotify.api_key');
        $this->senderId = config('services.mnotify.sender_id');
    }

    public function send(string $phoneNumber, string $message, ?int $registrationId = null, ?string $name = null): bool
    {
        // Every SMS is logged, whether or not it is tied to a bootcamp registration.
        $smsLog = SmsLog::create([
            'registration_id' => $registrationId,
            'name' => $name,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'status' => 'pending',
        ]);

        if (!$this->apiKey) {
            Log::warning('SMS notification skipped: MNotify API key not configured');
            $smsLog->update(['status' => 'failed', 'response' => 'MNotify API key not configured']);
            return false;
        }

        try {
            Log::info('Sending SMS', [
                'phone' => $phoneNumber,
                'sender_id' => $this->senderId,
                'message_length' => strlen($message),
            ]);

            $response = Http::post($this->apiUrl, [
                'key' => $this->apiKey,
                'recipient' => [$this->normalizePhoneNumber($phoneNumber)],
                'message' => $message,
                'sender' => $this->senderId,
            ]);

            Log::info('MNotify API response', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', ['phone' => $phoneNumber, 'response' => $response->json()]);
                if ($smsLog) {
                    $smsLog->update([
                        'status' => 'sent',
                        'response' => json_encode($response->json()),
                        'sent_at' => now(),
                    ]);
                }
                return true;
            }

            Log::error('SMS failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($smsLog) {
                $smsLog->update([
                    'status' => 'failed',
                    'response' => json_encode($response->json()),
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SMS exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($smsLog) {
                $smsLog->update([
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ]);
            }

            return false;
        }
    }

    public function sendRegistrationConfirmation(string $phoneNumber, string $firstName, ?int $registrationId = null): bool
    {
        $message = "Hi {$firstName}! Welcome to Applyd Academy. You're all set for the Digital Tools Bootcamp. Check your email for session details. Let's grow together.";

        return $this->send($phoneNumber, $message, $registrationId, $firstName);
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Ensure the phone number starts with + for international format
        $phoneNumber = trim($phoneNumber);
        if (!str_starts_with($phoneNumber, '+')) {
            // If it starts with 0, remove it and prepend the country code
            // This is a fallback; ideally the phone is already in +XXX format from the form
            if (str_starts_with($phoneNumber, '0')) {
                return '+233' . substr($phoneNumber, 1);
            }
            return '+' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
