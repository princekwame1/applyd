<?php

/*
|--------------------------------------------------------------------------
| Session check-in surveys ("Pulse Check")
|--------------------------------------------------------------------------
|
| Two short surveys participants fill in around a bootcamp session — one on
| arrival, one on the way out.
|
| The `questions` block below is only the migration seed, exactly like
| `bootcamp.tool_categories` is for the tools table. Once migrated, the
| questions live in the `survey_questions` table and are edited at
| /dashboard/surveys/questions — do not read this array at runtime.
|
| `types` IS read at runtime: it's the structural list of surveys, and the
| copy that wraps them.
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
