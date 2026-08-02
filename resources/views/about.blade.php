@extends('layouts.app')

@section('title', 'About — Applyd Academy')

@section('content')
<section class="alt contact-hero">
    <div class="container center">
        <h1 class="section-title">About Us</h1>
        <p class="section-lead">Questions about the bootcamp, partnerships, or anything else? We'd love to hear from you.</p>
    </div>
</section>
{{-- About / Problem–Solution --}}
<section id="about">
    <div class="container">
        <div class="split">
            <div>
                <h2 class="section-title">Technology moved fast. Most of us are still catching up.</h2>
                <p class="section-lead">Every job posting now expects it. Every business now runs on it. Project boards, AI assistants, cloud docs, scheduling tools, content platforms — the tools that used to be “nice to know” are now the baseline for getting hired, getting promoted, or growing a business.</p>
                <p class="section-lead">The problem isn't that these tools are hard. It's that nobody ever sat you down and showed you, step by step, how to actually use them. <strong>That's what the Digital Tools Bootcamp is for.</strong></p>
                <p class="section-lead">Over 24 days, you'll learn directly from working professionals — not theory, not slides you'll forget by tomorrow, but live demonstrations and hands-on practice you can apply the same day.</p>
            </div>
            <div class="split-media">
                <div class="img-frame">
                    <img src="{{ asset('img/learn-together.jpg') }}" alt="Learners collaborating over laptops" loading="lazy">
                </div>
                <span class="mini-card" aria-hidden="true">📅 24 Days &nbsp;·&nbsp; 🛠️ 24 Tools</span>
            </div>
        </div>
    </div>
</section>

{{-- What Makes This Different --}}
<section class="alt">
    <div class="container">
        <h2 class="section-title">What Makes This Different</h2>
        <p class="section-lead">This isn't another webinar series.</p>
        <div class="grid grid-3">
            <div class="card"><span class="icon">🛠️</span><h3>Practical, not theoretical</h3><p>Every session is built around live demos and real practice, not slides.</p></div>
            <div class="card"><span class="icon">🌍</span><h3>Genuinely global</h3><p>Facilitators from 3 countries bring different industries, tools, and perspectives.</p></div>
            <div class="card"><span class="icon">💼</span><h3>Built for the real world</h3><p>Every session ends with a business or career use case — how professionals actually use the tool.</p></div>
            <div class="card"><span class="icon">🤝</span><h3>A network, not just a class</h3><p>Join a cross-border community of learners, facilitators, and professionals.</p></div>
            <div class="card"><span class="icon">🎁</span><h3>Zero cost, zero catch</h3><p>Completely free. No hidden fees, no upsells.</p></div>
        </div>
    </div>
</section>

{{-- Who Should Attend --}}
<section>
    <div class="container">
        <div class="split flip">
            <div class="split-media">
                <div class="img-frame">
                    <img src="{{ asset('img/laptop-work.jpg') }}" alt="Two people working through a task on a laptop" loading="lazy">
                </div>
                <span class="mini-card" aria-hidden="true">🌍 Learners from 3 countries</span>
            </div>
            <div>
                <h2 class="section-title">Who Should Attend</h2>
                <p class="section-lead">Built for anyone ready to work smarter.</p>
                <ul class="check-list">
                    <li><strong>Students &amp; Fresh Graduates</strong> — Learn the tools employers already expect you to know.</li>
                    <li><strong>Working Professionals</strong> — Upgrade your productivity and stand out on your team.</li>
                    <li><strong>Entrepreneurs &amp; SME Owners</strong> — Run your business like a bigger one, without the overhead.</li>
                    <li><strong>Job Seekers</strong> — Close the exact skills gap standing between you and the offer.</li>
                    <li><strong>Career Changers</strong> — Build real digital skills without going back to school.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- What You'll Walk Away With --}}
<section class="alt">
    <div class="container">
        <div class="split">
            <div>
                <h2 class="section-title">What You'll Walk Away With</h2>
                <p class="section-lead">By day 24, you'll have real, usable skills — not just notes.</p>
                <ul class="check-list">
                    <li>Hands-on experience with industry-standard tools</li>
                    <li>Increased workplace productivity</li>
                    <li>Stronger collaboration and communication skills</li>
                    <li>Working knowledge of AI-powered productivity tools</li>
                    <li>Practical project management skills</li>
                    <li>Social media and digital marketing know-how</li>
                    <li>Genuine digital workplace readiness</li>
                    <li>A network of professionals across three countries</li>
                </ul>
            </div>
            <div class="split-media">
                <div class="img-frame">
                    <img src="{{ asset('img/workshop.jpg') }}" alt="Facilitator leading a hands-on workshop" loading="lazy">
                </div>
                <span class="mini-card" aria-hidden="true">🎓 Live, hands-on sessions</span>
            </div>
        </div>
    </div>
</section>

{{-- How It Works --}}
<section>
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-lead">One live session. Every few days. 100 minutes each.</p>
        <div class="steps">
            <div class="step"><div class="step-num">1</div><div><h3>Learn it</h3><p>A facilitator breaks down the tool and where it's used in the real world.</p></div></div>
            <div class="step"><div class="step-num">2</div><div><h3>Watch it</h3><p>Live demonstration, not a recording — ask questions in real time.</p></div></div>
            <div class="step"><div class="step-num">3</div><div><h3>Try it</h3><p>Guided hands-on practice during the session.</p></div></div>
            <div class="step"><div class="step-num">4</div><div><h3>Apply it</h3><p>See how professionals use it for business and career growth.</p></div></div>
            <div class="step"><div class="step-num">5</div><div><h3>Take the win</h3><p>Leave with a practical assignment to lock in what you learned.</p></div></div>
        </div>
        <p class="section-lead" style="margin-top: 28px; margin-bottom: 0;"><strong>24 sessions. Twice a week. Wednesdays and Saturdays.</strong> Join the ones you need, or complete the full journey.</p>
    </div>
</section>

{{-- Testimonials (placeholder quotes — replace with real learner feedback) --}}
<section class="alt">
    <div class="container">
        <div class="center">
            <h2 class="section-title">See What Our Learners Say</h2>
            <p class="section-lead">Real people, real progress — hear from learners across our community.</p>
        </div>
        <div class="grid grid-3">
            <div class="card t-card">
                <div class="t-stars">★★★★★</div>
                <span class="t-chip">Promoted at work</span>
                <p class="t-quote">“I set up our whole team on Trello and Notion the week after those sessions. My manager noticed — and so did the promotion board.”</p>
                <div class="t-person">
                    <span class="t-avatar">AK</span>
                    <div><strong>Ama K.</strong><br><small>Productivity track · Accra, Ghana</small></div>
                </div>
            </div>
            <div class="card t-card">
                <div class="t-stars">★★★★★</div>
                <span class="t-chip">Landed first job</span>
                <p class="t-quote">“I came in never having used ChatGPT properly. Two months later I walked into an interview and demoed an AI workflow. I got the offer.”</p>
                <div class="t-person">
                    <span class="t-avatar">CO</span>
                    <div><strong>Chinedu O.</strong><br><small>AI &amp; Automation track · Lagos, Nigeria</small></div>
                </div>
            </div>
            <div class="card t-card">
                <div class="t-stars">★★★★★</div>
                <span class="t-chip">Runs her business smarter</span>
                <p class="t-quote">“Canva and Buffer changed how my shop shows up online. I schedule a week of content in one evening now.”</p>
                <div class="t-person">
                    <span class="t-avatar">WM</span>
                    <div><strong>Wanjiku M.</strong><br><small>Design &amp; Marketing tracks · Nairobi, Kenya</small></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Stats band --}}
<section class="stats-band">
    <div class="container stats-row">
        <div><span class="num">24</span><span class="lbl">Digital tools taught</span></div>
        <div><span class="num">24</span><span class="lbl">Expert facilitators</span></div>
        <div><span class="num">3</span><span class="lbl">Countries represented</span></div>
        <div><span class="num">100%</span><span class="lbl">Free — no catch</span></div>
    </div>
</section>

{{-- FAQ --}}
<section class="alt faq">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <details><summary>Is this really free?</summary><p>Yes — 100% free, with no hidden costs.</p></details>
        <details><summary>Do I need any prior experience?</summary><p>No. Sessions are designed for beginners and are useful for intermediate users too.</p></details>
        <details><summary>Do I have to attend all 24 sessions?</summary><p>No — join the sessions most relevant to you, or complete the full journey for the full experience.</p></details>
        <details><summary>What do I need to join?</summary><p>Just a laptop or smartphone, an internet connection, and a free account with each tool as it's introduced.</p></details>
        <details><summary>Is this only for people in one country?</summary><p>No — this is a cross-border program with facilitators and participants from multiple countries.</p></details>
    </div>
</section>

{{-- Final CTA --}}
<section class="final-cta">
    <div class="container">
        <h2 class="section-title">No experience? No problem.</h2>
        <p class="section-lead" style="margin-left:auto; margin-right:auto;">You dream of a better career — we're the bridge. 24 days. 24 tools. Completely free.</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">Reserve Your Free Spot →</a>
        <p class="micro">No cost. No experience required. Cancel anytime.</p>
    </div>
</section>
@endsection
