@extends('layouts.app')

@section('title', 'About Applyd Academy')

@section('content')
<section class="alt contact-hero">
    <div class="container center">
        <h1 class="section-title">About Us</h1>
        <p class="section-lead">Know more about us and what makes us unique.</p>
    </div>
</section>
{{-- About / Problem–Solution --}}
<section id="about">
    <div class="container">
        <div class="split">
            <div>
                <h2 class="section-title">Technology moved fast. Most of us are still catching up.</h2>
                <p class=”section-lead”>Look at any job posting. Every one expects it. Every business runs on these tools now. Trello, Notion, ChatGPT, Figma, Buffer, Zapier. What used to be optional is now table stakes. You won't get hired, get promoted, or grow your business without them.</p>
                <p class=”section-lead”>But here's the thing. These tools aren't hard to learn. The real problem is nobody ever shows you. You're left watching YouTube tutorials or figuring it out yourself. That's where we come in. <strong>We're here to actually show you how.</strong></p>
                <p class=”section-lead”>Over 24 days, real professionals will walk you through this. Not lectures. Not slides you'll forget by next week. Live sessions where you practice, ask questions, and leave with skills you can use that same day.</p>
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
            <div class="card"><span class="icon">💼</span><h3>Built for the real world</h3><p>Every session ends with a real business or career use case. You'll see how professionals actually use these tools, not just how they work in theory.</p></div>
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
                    <li><strong>Students & Fresh Graduates</strong> Want to start your career with the right foundation. Master the tools that show up in every job posting.</li>
                    <li><strong>Working Professionals</strong> Ready to work smarter, not harder. Stand out in your role and unlock the next opportunity.</li>
                    <li><strong>Entrepreneurs & SME Owners</strong> Running a lean operation. Get your business operating at enterprise level, without enterprise overhead.</li>
                    <li><strong>Job Seekers</strong> Know exactly what's missing from your toolkit. Close that skills gap and nail the interview.</li>
                    <li><strong>Career Changers</strong> Need credible skills for your next chapter. Get hands-on experience without the school debt.</li>
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
                <p class="section-lead">By day 24, you'll have real skills you can use. Not just notes. Not just certificates. Real experience.</p>
                <ul class="check-list">
                    <li>You'll actually know how to use 24 industry-standard tools</li>
                    <li>You'll get your work done faster and smarter</li>
                    <li>You'll collaborate better with your team</li>
                    <li>You'll understand how AI can make you more productive</li>
                    <li>You'll be able to manage projects without the confusion</li>
                    <li>You'll know how to build a social media presence that converts</li>
                    <li>You'll walk into your next job or opportunity ready</li>
                    <li>You'll have a network of professionals in three countries who get it</li>
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
            <div class="step"><div class="step-num">2</div><div><h3>Watch it</h3><p>Live demonstration. Not a recording you can't pause or skip. Ask questions in real time and get real answers.</p></div></div>
            <div class="step"><div class="step-num">3</div><div><h3>Try it</h3><p>Guided hands-on practice during the session.</p></div></div>
            <div class="step"><div class="step-num">4</div><div><h3>Apply it</h3><p>See how professionals use it for business and career growth.</p></div></div>
            <div class="step"><div class="step-num">5</div><div><h3>Take the win</h3><p>Leave with a practical assignment to lock in what you learned.</p></div></div>
        </div>
        <p class="section-lead" style="margin-top: 28px; margin-bottom: 0;"><strong>24 sessions. Twice a week. Wednesdays and Saturdays.</strong> Join the ones you need, or complete the full journey.</p>
    </div>
</section>

{{-- Testimonials --}}
{{-- <section class=”alt”>
    <div class=”container”>
        <div class=”center”>
            <h2 class=”section-title”>See What Our Learners Say</h2>
            <p class=”section-lead”>Real people, real progress. Hear from learners across our community.</p>
        </div>

        <div class=”grid grid-3” style=”max-width: 900px; margin: 0 auto;”>
            <div class=”card t-card”>
                <div class=”t-stars”>★★★★★</div>
                <span class=”t-chip”>Promoted at work</span>
                <p class=”t-quote”>”I set up our whole team on Trello and Notion right after the sessions. My manager noticed, and my next review reflected it. The promotion came through two months later.”</p>
                <div class=”t-person”>
                    <span class=”t-avatar”>AK</span>
                    <div><strong>Ama K.</strong><br><small>Productivity track · Accra, Ghana</small></div>
                </div>
            </div>
            <div class=”card t-card”>
                <div class=”t-stars”>★★★★★</div>
                <span class=”t-chip”>Landed first job</span>
                <p class=”t-quote”>”ChatGPT was completely new to me when I started. After the bootcamp, I built an actual AI workflow and demoed it in my next interview. They hired me on the spot.”</p>
                <div class=”t-person”>
                    <span class=”t-avatar”>CO</span>
                    <div><strong>Chinedu O.</strong><br><small>AI &amp; Automation track · Lagos, Nigeria</small></div>
                </div>
            </div>
            <div class=”card t-card”>
                <div class=”t-stars”>★★★★★</div>
                <span class=”t-chip”>Runs her business smarter</span>
                <p class=”t-quote”>”Canva and Buffer completely transformed my social media game. What used to take me all week now takes one evening. My online presence is way stronger, and I actually have time to run my business.”</p>
                <div class=”t-person”>
                    <span class=”t-avatar”>WM</span>
                    <div><strong>Wanjiku M.</strong><br><small>Design &amp; Marketing tracks · Nairobi, Kenya</small></div>
                </div>
            </div>
        </div>
    </div>
</section> --}}

{{-- Stats band --}}
<section class="stats-band">
    <div class="container stats-row">
        <div><span class="num">24</span><span class="lbl">Digital tools taught</span></div>
        <div><span class="num">24</span><span class="lbl">Expert facilitators</span></div>
        <div><span class="num">3</span><span class="lbl">Countries represented</span></div>
        <div><span class="num">100%</span><span class="lbl">Free. No catch.</span></div>
    </div>
</section>

{{-- FAQ --}}
<section class="alt faq">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <details><summary>Is this really free?</summary><p>Yes. 100% free, with no hidden costs or upsells.</p></details>
        <details><summary>Do I need any prior experience?</summary><p>No. Sessions are designed for beginners and are useful for intermediate users too.</p></details>
        <details><summary>Do I have to attend all 24 sessions?</summary><p>No. Join the sessions that matter to you, or do all 24 for the complete experience. Your call.</p></details>
        <details><summary>What do I need to join?</summary><p>Just a laptop or smartphone, an internet connection, and a free account with each tool as it's introduced.</p></details>
        <details><summary>Is this only for people in one country?</summary><p>No. This is a cross-border program with facilitators and participants from Ghana, Nigeria, Kenya, and beyond.</p></details>
    </div>
</section>

{{-- Final CTA --}}
<section class="final-cta">
    <div class="container">
        <h2 class="section-title">No experience? No problem.</h2>
        <p class="section-lead" style="margin-left:auto; margin-right:auto;">You dream of a better career. We're the bridge. 24 days. 24 tools. Completely free.</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">Reserve Your Free Spot →</a>
        <p class="micro">No cost. No experience required. Cancel anytime.</p>
    </div>
</section>

@endsection
