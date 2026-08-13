<?php

/*
|--------------------------------------------------------------------------
| Session check-in surveys ("Pulse Check")
|--------------------------------------------------------------------------
|
| Short surveys participants fill in around a bootcamp session.
|
| NOTHING here is read at runtime — the whole file is the migration seed, the
| way `bootcamp.tool_categories` is for the tools table. `types` seeded the
| `surveys` table and `questions` seeded `survey_questions`; both are managed
| in the dashboard afterwards (/dashboard/surveys/manage and
| /dashboard/surveys/questions). Editing this file changes nothing on a
| database that has already migrated.
|
*/

return [

    'types' => [
        'pre' => [
            'label' => 'Before we start',
            'eyebrow' => '01 · Arriving',
            'blurb' => "Tell us what you're hoping to get out of today.",
            'thanks' => "We've got your answers. Enjoy the session!",
        ],
        'post' => [
            'label' => 'Before you go',
            'eyebrow' => '02 · Wrapping up',
            'blurb' => 'Tell us how the session went.',
            'thanks' => 'Thanks for the honest feedback — see you at the next one.',
        ],
    ],

    'questions' => [

        'pre' => [
            [
                'key' => 'motivation',
                'type' => 'choice',
                'prompt' => 'What made you sign up for this bootcamp?',
                'options' => [
                    'Career growth',
                    'Plain curiosity',
                    'Required for work',
                    'A friend told me to',
                    'Other',
                ],
                'required' => true,
            ],
            [
                'key' => 'goal',
                'type' => 'text',
                'prompt' => "What's the one thing you most want to walk away with today?",
                'placeholder' => 'Type your answer…',
                'required' => false,
            ],
            [
                'key' => 'comfort',
                'type' => 'scale',
                'prompt' => 'How comfortable are you with digital tools right now?',
                'options' => ['Beginner', 'Some experience', 'Comfortable', 'Advanced'],
                'required' => true,
            ],
            [
                'key' => 'source',
                'type' => 'choice',
                'prompt' => 'Where did you hear about this bootcamp?',
                'options' => [
                    'Instagram',
                    'Facebook',
                    'WhatsApp',
                    'Twitter / X',
                    'LinkedIn',
                    'Friend or colleague',
                    'Email',
                    'Flyer / poster',
                    'Other',
                ],
                'required' => true,
            ],
        ],

        'post' => [
            [
                'key' => 'expectations',
                'type' => 'scale',
                'prompt' => "How well did today's session meet your expectations?",
                'options' => ['Not at all', 'A little', 'Mostly', 'Fully', 'Exceeded them'],
                'required' => true,
            ],
            [
                'key' => 'valuable',
                'type' => 'text',
                'prompt' => "What was the most valuable part of today's session?",
                'placeholder' => 'Type your answer…',
                'required' => false,
            ],
            [
                'key' => 'improve',
                'type' => 'text',
                'prompt' => 'What should we improve for next time?',
                'placeholder' => 'Type your answer…',
                'required' => false,
            ],
        ],

    ],

];
