<?php

/*
|--------------------------------------------------------------------------
| CMS content registry
|--------------------------------------------------------------------------
| Every editable piece of front-end content is declared here, grouped by
| page and section. `default` is the current hard-coded copy (used until an
| admin overrides it). Types: text | textarea | richtext | image.
| Front-end reads a field with cms('<page>','<key>') / cms_image(...).
*/

return [
    'pages' => [

        'landing' => [
            'label' => 'Landing (Home)',
            'route' => 'landing',
            'sections' => [
                'Hero' => ['fields' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Eyebrow', 'default' => 'Applyd Academy Presents'],
                    'hero_title_1' => ['type' => 'text', 'label' => 'Title line 1', 'default' => 'Master the Tools.'],
                    'hero_title_2' => ['type' => 'text', 'label' => 'Title line 2 (accent)', 'default' => 'Accelerate Your Future.'],
                    'hero_sub' => ['type' => 'textarea', 'label' => 'Subtitle', 'default' => "A free, hands-on learning experience. In just 24 days, you'll learn 24 digital tools from expert facilitators across 3 countries."],
                    'hero_chip_1' => ['type' => 'text', 'label' => 'Feature chip 1', 'default' => 'Learn through live, practical sessions'],
                    'hero_chip_2' => ['type' => 'text', 'label' => 'Feature chip 2', 'default' => '24 expert facilitators, 3 countries'],
                ]],
                'Register form' => ['fields' => [
                    'form_title' => ['type' => 'text', 'label' => 'Form title', 'default' => 'Reserve Your Free Spot'],
                    'form_sub' => ['type' => 'text', 'label' => 'Form subtitle', 'default' => '24 days. 24 tools. Completely free. Spots are limited per session.'],
                    'form_next' => ['type' => 'text', 'label' => 'Next button', 'default' => 'Next →'],
                    'form_back' => ['type' => 'text', 'label' => 'Back button', 'default' => '← Back'],
                    'form_submit' => ['type' => 'text', 'label' => 'Submit button', 'default' => 'Reserve Your Free Spot →'],
                ]],
                'Problem section' => ['fields' => [
                    'prob_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Technology moved fast. Most of us are still catching up.'],
                    'prob_p1' => ['type' => 'textarea', 'label' => 'Paragraph 1', 'default' => "Look at any job posting. Every one expects it. Every business runs on these tools now. Trello, Notion, ChatGPT, Figma, Buffer, Zapier. What used to be optional is now table stakes. You won't get hired, get promoted, or grow your business without them."],
                    'prob_p2' => ['type' => 'textarea', 'label' => 'Paragraph 2', 'default' => "But here's the thing. These tools aren't hard to learn. The real problem is nobody ever shows you. You're left watching YouTube tutorials or figuring it out yourself. That's where we come in. <strong>We're here to actually show you how.</strong>"],
                    'prob_p3' => ['type' => 'textarea', 'label' => 'Paragraph 3', 'default' => "Over 24 days, real professionals will walk you through this. Not lectures. Not slides you'll forget by next week. Live sessions where you practice, ask questions, and leave with skills you can use that same day."],
                    'prob_image' => ['type' => 'image', 'label' => 'Image', 'default' => 'img/learn-together.jpg'],
                    'prob_badge' => ['type' => 'text', 'label' => 'Image badge', 'default' => '📅 24 Days · 🛠️ 24 Tools'],
                ]],
                'Who Should Attend' => ['fields' => [
                    'attend_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Who Should Attend'],
                    'attend_lead' => ['type' => 'text', 'label' => 'Lead', 'default' => 'Built for anyone ready to work smarter.'],
                    'attend_image' => ['type' => 'image', 'label' => 'Image', 'default' => 'img/laptop-work.jpg'],
                    'attend_badge' => ['type' => 'text', 'label' => 'Image badge', 'default' => '🌍 Learners from 3 countries'],
                ]],
                "What You'll Walk Away With" => ['fields' => [
                    'walk_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => "What You'll Walk Away With"],
                    'walk_lead' => ['type' => 'text', 'label' => 'Lead', 'default' => "By day 24, you'll have real skills you can use. Not just notes. Not just certificates. Real experience."],
                    'walk_image' => ['type' => 'image', 'label' => 'Image', 'default' => 'img/workshop.jpg'],
                    'walk_badge' => ['type' => 'text', 'label' => 'Image badge', 'default' => '🎓 Live, hands-on sessions'],
                ]],
                'What Makes This Different' => ['fields' => [
                    'diff_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'What Makes This Different'],
                    'diff_lead' => ['type' => 'text', 'label' => 'Lead', 'default' => "This isn't another webinar series."],
                ]],
                'Stats band' => ['fields' => [
                    'stat1_num' => ['type' => 'text', 'label' => 'Stat 1 — number', 'default' => '24'],
                    'stat1_lbl' => ['type' => 'text', 'label' => 'Stat 1 — label', 'default' => 'Digital tools taught'],
                    'stat2_num' => ['type' => 'text', 'label' => 'Stat 2 — number', 'default' => '24'],
                    'stat2_lbl' => ['type' => 'text', 'label' => 'Stat 2 — label', 'default' => 'Expert facilitators'],
                    'stat3_num' => ['type' => 'text', 'label' => 'Stat 3 — number', 'default' => '3'],
                    'stat3_lbl' => ['type' => 'text', 'label' => 'Stat 3 — label', 'default' => 'Countries represented'],
                    'stat4_num' => ['type' => 'text', 'label' => 'Stat 4 — number', 'default' => '100%'],
                    'stat4_lbl' => ['type' => 'text', 'label' => 'Stat 4 — label', 'default' => 'Free. No catch.'],
                ]],
                'Final CTA' => ['fields' => [
                    'cta_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'No experience? No problem.'],
                    'cta_sub' => ['type' => 'textarea', 'label' => 'Subtitle', 'default' => "You dream of a better career. We're the bridge. 24 days. 24 tools. Completely free."],
                    'cta_button' => ['type' => 'text', 'label' => 'Button text', 'default' => 'Reserve Your Free Spot →'],
                ]],
            ],
        ],

        'about' => [
            'label' => 'About',
            'route' => 'about',
            'sections' => [
                'Hero' => ['fields' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Eyebrow', 'default' => 'About Applyd Academy'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => "We're closing Africa's marketing skills gap"],
                    'hero_sub' => ['type' => 'text', 'label' => 'Subtitle', 'default' => 'One practical, employable marketer at a time.'],
                ]],
                'The Gap' => ['fields' => [
                    'gap_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Certificates on paper. Silence in the room.'],
                    'gap_p1' => ['type' => 'textarea', 'label' => 'Paragraph 1', 'default' => 'Every year, thousands of graduates leave school with certificates but no clue how to run a real campaign, pitch a real client, or use the tools a marketing job actually demands.'],
                    'gap_p2' => ['type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Every year, companies interview candidate after candidate who looks qualified on paper and falls short in the room.'],
                    'gap_emphasis' => ['type' => 'text', 'label' => 'Emphasis line', 'default' => 'That gap is what Applyd Academy exists to close.'],
                    'gap_image' => ['type' => 'image', 'label' => 'Image', 'default' => 'img/learn-together.jpg'],
                ]],
                'What We Do' => ['fields' => [
                    'wwd_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'What We Do'],
                    'wwd_lead' => ['type' => 'textarea', 'label' => 'Lead', 'default' => 'Applyd Academy is more than a school. We\'re the digital headquarters for a full marketing ecosystem, education, recruitment, research, consulting, media, and community, where each part strengthens the others to produce marketers who are ready from day one.'],
                    'pullquote' => ['type' => 'text', 'label' => 'Pull quote', 'default' => 'We build capability.'],
                ]],
                'Who We Serve' => ['fields' => [
                    'serve_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Who We Serve'],
                    'serve_lead' => ['type' => 'text', 'label' => 'Lead', 'default' => 'We work with two audiences, side by side.'],
                ]],
                'What Makes Us Different' => ['fields' => [
                    'diff_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'What Makes Us Different'],
                    'stamp' => ['type' => 'text', 'label' => 'Banner slogan', 'default' => 'Skills, Not Stamps.'],
                ]],
                'Our Story' => ['fields' => [
                    'story_heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Our Story'],
                    'story_image' => ['type' => 'image', 'label' => 'Image', 'default' => 'img/workshop.jpg'],
                ]],
                'Mission & Vision' => ['fields' => [
                    'mission' => ['type' => 'textarea', 'label' => 'Mission', 'default' => "To build Africa's most practical, employable, and future-ready marketing talent."],
                    'vision' => ['type' => 'textarea', 'label' => 'Vision', 'default' => "To become the continent's leading marketing ecosystem by 2030."],
                ]],
                'Values & CTA' => ['fields' => [
                    'values_heading' => ['type' => 'text', 'label' => 'Values heading', 'default' => 'Our Values'],
                    'cta_heading' => ['type' => 'text', 'label' => 'CTA heading', 'default' => 'Ready to Begin?'],
                    'cta_sub' => ['type' => 'textarea', 'label' => 'CTA subtitle', 'default' => 'Join the movement closing Africa\'s marketing skills gap, one practical, employable, future-ready marketer at a time.'],
                ]],
            ],
        ],

        'contact' => [
            'label' => 'Contact',
            'route' => 'contact',
            'sections' => [
                'Hero' => ['fields' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Eyebrow', 'default' => 'Get in Touch'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Contact Us'],
                    'hero_sub' => ['type' => 'text', 'label' => 'Subtitle', 'default' => "Questions about the bootcamp, partnerships, or anything else? We'd love to hear from you."],
                ]],
                'General Enquiries' => ['fields' => [
                    'gen_text' => ['type' => 'text', 'label' => 'Description', 'default' => 'For programme info, partnerships, and media.'],
                    'gen_email' => ['type' => 'text', 'label' => 'Email', 'default' => 'info@applydacademy.com'],
                ]],
                'Support' => ['fields' => [
                    'sup_text' => ['type' => 'text', 'label' => 'Description', 'default' => 'Registration issues, session access, or technical help.'],
                    'sup_email' => ['type' => 'text', 'label' => 'Email', 'default' => 'support@applydacademy.com'],
                ]],
                'Visit Us' => ['fields' => [
                    'visit_address' => ['type' => 'textarea', 'label' => 'Address', 'default' => "Trade Fair, 25 Giffard Rd,\nAccra, Ghana"],
                ]],
                'Form' => ['fields' => [
                    'form_heading' => ['type' => 'text', 'label' => 'Form heading', 'default' => 'Send us a Message'],
                ]],
            ],
        ],

        'jobs' => [
            'label' => 'Jobs (hero)',
            'route' => 'jobs',
            'sections' => [
                'Hero' => ['fields' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Eyebrow', 'default' => 'Careers'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Jobs & Opportunities'],
                    'hero_sub' => ['type' => 'text', 'label' => 'Subtitle', 'default' => 'Openings from companies in our network. Apply with your CV. No account needed.'],
                    'hero_cta' => ['type' => 'text', 'label' => 'CTA button', 'default' => 'Are you hiring? Post a job'],
                ]],
            ],
        ],

        'courses' => [
            'label' => 'Courses (hero)',
            'route' => 'courses',
            'sections' => [
                'Hero' => ['fields' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Eyebrow', 'default' => 'Applyd Academy'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Our Courses'],
                    'hero_sub' => ['type' => 'text', 'label' => 'Subtitle', 'default' => 'Learn from industry experts and advance your career.'],
                ]],
            ],
        ],

    ],
];
