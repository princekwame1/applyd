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
            // From has to stay on our own domain: Mailgun will only send as
            // a domain it has verified, and a visitor's gmail.com in the From
            // header is either refused outright or delivered as a forgery the
            // receiving side distrusts. The visitor goes in Reply-To instead,
            // so hitting reply in the inbox still lands back on them.
            $mail->to(config('mail.from.address'))
                ->replyTo($validated['email'], $validated['name'])
                ->subject("Contact Form: {$validated['subject']}");
        });

        return redirect()
            ->route('contact')
            ->with('contact_success', true);
    }
}
