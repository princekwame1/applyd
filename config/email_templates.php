<?php

/*
|--------------------------------------------------------------------------
| Transactional email registry
|--------------------------------------------------------------------------
| Every customisable email the app sends is declared here. The values below
| are the defaults; an admin can override subject/heading/body/CTA per
| template at /dashboard/email-templates (stored in `email_templates`).
|
| Placeholders use {{ token }} syntax and are substituted at send time —
| see App\Services\EmailNotificationService::render().
*/

return [

    'templates' => [

        'registration_confirmation' => [
            'label' => 'Bootcamp registration confirmation',
            'description' => 'Sent to the participant the moment they reserve a spot from the landing page. This is the email the "Resend email" buttons re-send.',
            'audience' => 'Bootcamp registrants',
            'subject' => "You're in, {{ first_name }} — Digital Tools Bootcamp",
            'heading' => 'Your spot is reserved',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>Welcome to <strong>Applyd Academy</strong>. Your spot on the Digital Tools Bootcamp is confirmed — 24 days, 24 tools, taught live by expert facilitators.</p>
                <p>Here is what we have on file for you:</p>
                <ul>
                    <li>Name: {{ full_name }}</li>
                    <li>Email: {{ email }}</li>
                    <li>Phone: {{ phone }}</li>
                    <li>Location: {{ city }}, {{ country }}</li>
                    <li>Tools you picked: {{ tools }}</li>
                </ul>
                <p>Keep an eye on this inbox — session details and joining links land here before we start. If anything above is wrong, just reply to this email and we'll fix it.</p>
                <p>See you in class.</p>
                HTML,
            'cta_label' => 'View the bootcamp schedule',
            'cta_url' => '{{ site_url }}',
        ],

    ],

    /*
    | Tokens available to every template. Keys map to the array returned by
    | EmailNotificationService::variablesFor().
    */
    'placeholders' => [
        'first_name' => "Registrant's first name",
        'full_name' => 'Full name as submitted',
        'email' => 'Email address',
        'phone' => 'Phone number with country code',
        'country' => 'Country',
        'city' => 'City',
        'education' => 'Level of education',
        'tools' => 'Comma-separated list of tools they selected',
        'registered_at' => 'Date they registered',
        'site_name' => 'Site name (APP_NAME)',
        'site_url' => 'Site URL (APP_URL)',
    ],

];
