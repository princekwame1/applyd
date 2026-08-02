<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $emailBody = "Name: {$validated['name']}\n"
            . "Email: {$validated['email']}\n"
            . "Subject: {$validated['subject']}\n\n"
            . "Message:\n{$validated['message']}";

        Mail::raw($emailBody, function ($mail) use ($validated) {
            $mail->to('info@applydacademy.com')
                ->from($validated['email'], $validated['name'])
                ->subject("Contact Form: {$validated['subject']}");
        });

        return redirect()
            ->route('contact')
            ->with('contact_success', true);
    }
}
