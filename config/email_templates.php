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

        'student_credentials' => [
            'label' => 'Student ID & portal login',
            'description' => 'Sent the moment a course registration is completed — it carries the student ID and the temporary password for the learning portal. This is what the "Resend login details" button on a course registration re-sends (with a freshly generated password).',
            'audience' => 'New students',
            'subject' => 'Your student ID and login — {{ site_name }}',
            'heading' => 'Welcome, {{ first_name }} — here are your details',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>Your registration for <strong>{{ course_title }}</strong> is complete. Here is everything you need to get into the learning portal, where your timetable, materials, assignments and results live.</p>
                <ul>
                    <li>Student ID: <strong>{{ student_id }}</strong></li>
                    <li>Sign in with: {{ email }}</li>
                    <li>{{ password_line }}</li>
                </ul>
                <p>Please change that password as soon as you sign in — you'll be asked to set your own before you go any further.</p>
                <p>Keep your student ID somewhere safe. It's how we identify you on everything from attendance to your transcript.</p>
                HTML,
            'cta_label' => 'Sign in to the portal',
            'cta_url' => '{{ login_url }}',
            // Declared per-template: none of the bootcamp registration tokens
            // mean anything here, and offering them would only mislead.
            'placeholders' => [
                'first_name' => "Student's first name",
                'full_name' => 'Full name as registered',
                'student_id' => 'The issued 8-digit student ID, e.g. 20260007',
                'course_title' => 'Course they registered for',
                'email' => 'Email address, which is also their username',
                'temp_password' => 'The temporary password (blank if they already had an account)',
                'password_line' => 'A ready-made line: the temporary password, or a note to use their existing one',
                'login_url' => 'Learning portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

    ],

    /*
    | Default tokens, used by any template that doesn't declare its own
    | `placeholders` list. Keys map to the array returned by
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
