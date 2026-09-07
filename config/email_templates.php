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

        'facilitator_credentials' => [
            'label' => 'Facilitator portal login',
            'description' => 'Sent when a facilitator account is created on /dashboard/facilitators, and again by the "Resend login details" button. It carries the temporary password for the learning portal, where their classes, registers and marking live.',
            'audience' => 'Facilitators / instructors',
            'subject' => 'Your facilitator login for {{ site_name }}',
            'heading' => "Welcome aboard, {{ first_name }}",
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>You have been set up as a facilitator on {{ site_name }}. Everything you need for teaching lives in the learning portal: your classes, the timetable, attendance registers, course materials, assignments and marking.</p>
                <ul>
                    <li>Sign in with: {{ email }}</li>
                    <li>{{ password_line }}</li>
                </ul>
                <p>Please set your own password as soon as you sign in. You will be asked for one before you go any further.</p>
                <p>If a class you expect to see is missing, tell the academy office and they will add you to it.</p>
                HTML,
            'cta_label' => 'Sign in to the portal',
            'cta_url' => '{{ login_url }}',
            // Its own list: a facilitator has no student ID and no course
            // registration, so the student tokens would only mislead.
            'placeholders' => [
                'first_name' => "Facilitator's first name",
                'full_name' => 'Full name as recorded',
                'email' => 'Email address, which is also their username',
                'temp_password' => 'The temporary password (blank if they already had an account)',
                'password_line' => 'A ready-made line: the temporary password, or a note to use their existing one',
                'login_url' => 'Learning portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        // ------------------------------------------------ job board: companies

        'company_registered' => [
            'label' => 'Company registration received',
            'description' => 'Sent the moment an employer signs up at /companies/register. Says their details are being checked and that they can start posting in the meantime.',
            'audience' => 'Employers / job posters',
            'subject' => 'We have your details, {{ company_name }}',
            'heading' => 'Thanks for registering',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>Thanks for registering <strong>{{ company_name }}</strong> on {{ site_name }}. Your account is ready to use.</p>
                <p>Before anything you post appears on the public job board, we confirm who is behind the account — we check the Ghana Card details of every job poster. It protects the people applying, and it protects genuine employers from being lumped in with the ones who aren't.</p>
                <p><strong>You don't have to wait.</strong> Sign in now and write your job posts; each one goes live as soon as both checks are done. We'll email you the moment your account is verified.</p>
                <p>If we need anything else from you, we'll be in touch at this address.</p>
                HTML,
            'cta_label' => 'Go to your company portal',
            'cta_url' => '{{ portal_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        'company_approved' => [
            'label' => 'Company verified',
            'description' => 'Sent when an admin approves a company on /dashboard/companies. From here its approved postings appear on the public board.',
            'audience' => 'Employers / job posters',
            'subject' => '{{ company_name }} is verified',
            'heading' => "You're verified",
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>We've checked the details for <strong>{{ company_name }}</strong> and your account is now verified on {{ site_name }}.</p>
                <p>Your job posts will appear on the public board as soon as each one has been read — usually the same working day. Anything you posted while waiting is already in the queue; there is nothing to resubmit.</p>
                <p>Thanks for taking the time to identify yourself properly. It is the reason candidates trust what they find here.</p>
                HTML,
            'cta_label' => 'Post a job',
            'cta_url' => '{{ portal_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        'company_rejected' => [
            'label' => 'Company not verified',
            'description' => 'Sent when an admin rejects a company, carrying the reason they gave. Without this email a rejected employer only sees a status change and has no idea what to fix.',
            'audience' => 'Employers / job posters',
            'subject' => 'We could not verify {{ company_name }}',
            'heading' => 'We need something more from you',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>We weren't able to verify <strong>{{ company_name }}</strong> on {{ site_name }} yet. Here is why:</p>
                <blockquote style="margin:0 0 18px; padding:12px 16px; background:#faeaeb; border-left:3px solid #c73a41; border-radius:6px;">{{ reason }}</blockquote>
                <p>This isn't the end of it. Reply to this email with what's needed and we'll take another look — most of these are settled the same day.</p>
                <p>Your postings stay off the public board until then, but nothing you've written has been deleted.</p>
                HTML,
            'cta_label' => 'Go to your company portal',
            'cta_url' => '{{ portal_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'reason' => 'The reason the reviewer gave — always include this',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        // --------------------------------------------- job board: the postings

        'job_submitted' => [
            'label' => 'Job posting received',
            'description' => 'Sent when an employer publishes a job from their portal. Confirms it is queued for review rather than already live.',
            'audience' => 'Employers / job posters',
            'subject' => 'Received: {{ job_title }}',
            'heading' => 'Your posting is in review',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>We have your posting for <strong>{{ job_title }}</strong>. Someone reads every job before it reaches the board, so it isn't public just yet — usually the same working day.</p>
                <p>You'll get an email the moment it goes live. You can keep editing it in the meantime.</p>
                HTML,
            'cta_label' => 'View your postings',
            'cta_url' => '{{ portal_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'job_title' => 'Title of the job posting',
                'job_url' => 'Public link to the posting',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        'job_approved' => [
            'label' => 'Job posting is live',
            'description' => 'Sent when an admin approves a posting on /dashboard/job-postings. Carries the public link to it.',
            'audience' => 'Employers / job posters',
            'subject' => '{{ job_title }} is live',
            'heading' => 'Your posting is on the board',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p><strong>{{ job_title }}</strong> is now live on the {{ site_name }} job board and open for applications.</p>
                <p>Applications arrive in your company portal, where you can shortlist candidates and download their CVs.</p>
                HTML,
            'cta_label' => 'See it on the board',
            'cta_url' => '{{ job_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'job_title' => 'Title of the job posting',
                'job_url' => 'Public link to the posting',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],

        'job_rejected' => [
            'label' => 'Job posting declined',
            'description' => 'Sent when an admin rejects a posting, carrying the reason. The employer can edit and resubmit.',
            'audience' => 'Employers / job posters',
            'subject' => 'We could not publish {{ job_title }}',
            'heading' => 'This posting needs a change',
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>We haven't put <strong>{{ job_title }}</strong> on the board. Here is why:</p>
                <blockquote style="margin:0 0 18px; padding:12px 16px; background:#faeaeb; border-left:3px solid #c73a41; border-radius:6px;">{{ reason }}</blockquote>
                <p>Edit the posting in your portal and it goes straight back into the queue — there's no need to write it again from scratch.</p>
                HTML,
            'cta_label' => 'Edit the posting',
            'cta_url' => '{{ portal_url }}',
            'placeholders' => [
                'first_name' => "Contact person's first name",
                'contact_name' => 'Contact person, in full',
                'company_name' => 'Registered company name',
                'ghana_card' => 'Ghana Card number on file',
                'job_title' => 'Title of the job posting',
                'job_url' => 'Public link to the posting',
                'reason' => 'The reason the reviewer gave — always include this',
                'portal_url' => 'Company portal sign-in URL',
                'site_name' => 'Site name (APP_NAME)',
                'site_url' => 'Site URL (APP_URL)',
            ],
        ],


        // ----------------------------------------------- digital products

        'product_delivery' => [
            'label' => 'Digital product — download link',
            'description' => 'Sent the moment a purchase is settled (or straight away for a free download). It carries the buyer\'s own download link, which is the only way to the file. This is the email the "Resend download" button on an order re-sends.',
            'audience' => 'Buyers of a digital product',
            'subject' => 'Your download is ready: {{ product_title }}',
            'heading' => "Thanks, {{ first_name }}. Here's your download",
            'body' => <<<'HTML'
                <p>Hi {{ first_name }},</p>
                <p>Your purchase of <strong>{{ product_title }}</strong> is complete. The button below opens your download page, where the file is waiting for you.</p>
                <ul>
                    <li>Item: {{ product_title }}</li>
                    <li>Paid: {{ amount }}</li>
                    <li>Reference: {{ reference }}</li>
                </ul>
                <p>That link is yours. Keep this email and you can come back to the download whenever you need it. Please don't forward it on, because anyone who has the link has the file.</p>
                <p>If the download doesn't work, just reply to this email and we'll sort it out.</p>
                HTML,
            'cta_label' => 'Open your download',
            'cta_url' => '{{ download_url }}',
            // Its own list: nothing about a bootcamp registration means
            // anything to somebody who has just bought a template.
            'placeholders' => [
                'first_name' => "Buyer's first name",
                'full_name' => 'Buyer name as entered at checkout',
                'email' => 'Buyer email address',
                'product_title' => 'What they bought',
                'amount' => 'What they paid, e.g. GHS 50.00 (or "Free")',
                'reference' => 'Order reference',
                'download_url' => 'The buyer\'s private download link, which is the whole point of this email',
                'purchased_at' => 'Date the payment settled',
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
