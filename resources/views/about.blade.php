@extends('layouts.app')

@section('title', 'About Us — Applyd Academy')

@section('content')
{{-- Hero --}}
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">About Applyd Academy</span>
        <h1 class="section-title">We're closing Africa's marketing skills gap</h1>
        <p class="section-lead">One practical, employable marketer at a time.</p>
    </div>
</section>

{{-- The Gap --}}
<section id="about">
    <div class="container">
        <div class="split">
            <div>
                <h2 class="section-title">Certificates on paper. Silence in the room.</h2>
                <p class="section-lead">Every year, thousands of graduates leave school with certificates but no clue how to run a real campaign, pitch a real client, or use the tools a marketing job actually demands.</p>
                <p class="section-lead">Every year, companies interview candidate after candidate who looks qualified on paper and falls short in the room.</p>
                <p class="about-emphasis">That gap is what Applyd Academy exists to close.</p>
                <p class="section-lead">We are Africa's next-generation marketing institution, a practical, industry-led training and talent development ecosystem built for people who want to <span class="hl">do the work</span>, not just describe it in an interview.</p>
            </div>
            <div class="split-media">
                <div class="img-frame">
                    <img src="{{ asset('img/learn-together.jpg') }}" alt="Learners collaborating over laptops" loading="lazy">
                </div>
                <span class="mini-card" aria-hidden="true">Skills, not stamps</span>
            </div>
        </div>
    </div>
</section>

{{-- What We Do --}}
<section class="alt">
    <div class="container">
        <div class="center">
            <h2 class="section-title">What We Do</h2>
            <p class="section-lead" style="margin-left:auto;margin-right:auto;">Applyd Academy is more than a school. We're the digital headquarters for a full marketing ecosystem, education, recruitment, research, consulting, media, and community, where each part strengthens the others to produce marketers who are ready from day one.</p>
        </div>

        <div class="model-split">
            <div class="model-block">
                <span class="model-num">20<span>%</span></span>
                <span class="model-lbl">Theory</span>
            </div>
            <div class="model-block model-block--brand">
                <span class="model-num">80<span>%</span></span>
                <span class="model-lbl">Hands-on practice</span>
            </div>
            <p class="model-note">Learners work on live client projects, sharpen skills in simulation labs, use real AI and marketing tools, and get career support that runs from day one all the way to job offer.</p>
        </div>

        <div class="pull-quote">
            <p>We don't hand out certificates. <span class="hl">We build capability.</span></p>
            <small>Already certified but can't yet do the job? We're the ones who get you there, you shouldn't lose an opportunity for lacking a practical skill a piece of paper can't teach.</small>
        </div>

        <h3 class="eco-heading">Beyond training, the Applyd ecosystem includes</h3>
        <div class="grid grid-3">
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'briefcase'])</span>
                <h3>Consulting</h3>
                <p>Transformation projects for brands that need real marketing capability, not just recommendations.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'research'])</span>
                <h3>Training &amp; Research</h3>
                <p>Industry reports and salary benchmarks that keep our curriculum and our clients ahead of the market.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'megaphone'])</span>
                <h3>Media &amp; Marketing Agency</h3>
                <p>Content and thought leadership that shapes the conversation.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'users'])</span>
                <h3>Recruitment</h3>
                <p>Connecting job-ready graduates directly to employers.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'heart'])</span>
                <h3>Foundation</h3>
                <p>Scholarships and community impact that keep opportunity open to everyone, not just those who can afford it.</p>
            </div>
        </div>
    </div>
</section>

{{-- Who We Serve --}}
<section>
    <div class="container">
        <div class="center">
            <h2 class="section-title">Who We Serve</h2>
            <p class="section-lead" style="margin-left:auto;margin-right:auto;">We work with two audiences, side by side.</p>
        </div>
        <div class="grid grid-2 serve-grid">
            <div class="card serve-card">
                <span class="serve-tag">Individuals</span>
                <p>Fresh graduates and National Service personnel, junior and mid-level managers, marketing managers, entrepreneurs, content creators, freelancers, and agency owners.</p>
                <p class="serve-hook">If you're 22–45, tired of academic fluff, and need skills you can use on Monday morning, you're who we built this for.</p>
            </div>
            <div class="card serve-card">
                <span class="serve-tag">Corporates</span>
                <p>Banks, insurers, telcos, mining and manufacturing companies, FMCGs, NGOs, government institutions, SMEs, universities, and hospitals.</p>
                <p class="serve-hook">We help HR leads and CEOs close two gaps: upskilling credentialed-but-not-capable staff, and onboarding new hires with intensive, hands-on training before they touch a live campaign.</p>
            </div>
        </div>
        <p class="section-lead center" style="max-width:820px;margin:28px auto 0;">Whether you're a career switcher building your first portfolio or an organization identifying genuinely skill-ready talent, we solve the same problem: <span class="hl">the distance between knowing about marketing and being able to do marketing.</span></p>
    </div>
</section>

{{-- What Makes Us Different --}}
<section class="alt">
    <div class="container">
        <div class="center">
            <h2 class="section-title">What Makes Us Different</h2>
            <p class="section-lead" style="margin-left:auto;margin-right:auto;">Most providers compete on more certificates, more credentials, more letters after your name. We made a different bet, we replaced certification with <span class="hl">proof of work</span>. Less paperwork, more portfolio.</p>
        </div>

        <div class="compare">
            <div class="compare-head">
                <div class="trad">Traditional Certification Bodies</div>
                <div class="applyd">Applyd Academy</div>
            </div>
            @php
                $rows = [
                    ['Hand you a certificate that makes your résumé look good.', 'Hand you a portfolio, practical experience, and real-world skills employers can immediately evaluate.'],
                    ['Teach primarily through lectures, exams, and quizzes.', 'Teaches through live projects, hands-on practice, and active use of industry tools.'],
                    ['Rely heavily on academic professors and theoretical instruction.', 'Led by experienced industry practitioners actively working in the field.'],
                    ['Move slowly due to accreditation and curriculum approval cycles.', 'Moves quickly, continuously adapting to technology and industry demands.'],
                    ['Focus significant resources on compliance, accreditation, and testing.', 'Invests in learner tools, mentorship, practical experience, and measurable career outcomes.'],
                    ['Measure success by course completion and certification.', 'Measures success by skills mastery, portfolio, employability, and career growth.'],
                    ['Prepare learners to pass exams.', 'Prepares learners to perform, deliver results, and thrive in the workplace.'],
                    ['Graduates leave with proof of attendance.', 'Graduates leave with proof of capability.'],
                ];
            @endphp
            @foreach ($rows as $row)
                <div class="compare-row">
                    <div class="trad"><span class="cmp-label">Traditional</span>{{ $row[0] }}</div>
                    <div class="applyd"><span class="cmp-label">Applyd Academy</span>{{ $row[1] }}</div>
                </div>
            @endforeach
        </div>

        <div class="stamp-banner">
            <span class="stamp-sub">Our value proposition is simple</span>
            <span class="stamp-title">Skills, Not Stamps.</span>
        </div>
    </div>
</section>

{{-- Our Story --}}
<section>
    <div class="container">
        <div class="split flip">
            <div class="split-media">
                <div class="img-frame">
                    <img src="{{ asset('img/workshop.jpg') }}" alt="A hands-on Applyd Academy workshop" loading="lazy">
                </div>
                <span class="mini-card" aria-hidden="true">From a gap to a movement</span>
            </div>
            <div>
                <h2 class="section-title">Our Story</h2>
                <p class="section-lead">Applyd Academy began with an uncomfortable observation: the gap between what marketing schools teach and what employers actually need has never been wider.</p>
                <p class="section-lead">Rather than build another lecture-heavy curriculum to add to the pile, we built something different, a model rooted in live projects, simulation labs, real AI tools, and career support that doesn't stop at graduation.</p>
                <p class="section-lead">What started as a response to a gap has grown into a movement. Today, Applyd Academy sits at the heart of <strong>The Ansongs</strong>, a connected network spanning consulting, research, media, recruitment, and a foundation, all working together as a self-reinforcing ecosystem for marketing excellence across Africa.</p>
            </div>
        </div>
    </div>
</section>

{{-- Mission & Vision --}}
<section class="alt">
    <div class="container">
        <div class="grid grid-2 mv-grid">
            <div class="card mv-card">
                <span class="mv-tag">Our Mission</span>
                <p>To build Africa's most practical, employable, and future-ready marketing talent.</p>
            </div>
            <div class="card mv-card mv-card--brand">
                <span class="mv-tag">Our Vision</span>
                <p>To become the continent's leading marketing ecosystem by <strong>2030</strong>.</p>
            </div>
        </div>
    </div>
</section>

{{-- Values --}}
<section>
    <div class="container">
        <div class="center">
            <h2 class="section-title">Our Values</h2>
        </div>
        <div class="grid grid-3 values-grid">
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'tool'])</span>
                <h3>Practicality</h3>
                <p>We teach what works in the real world, not just what's in the textbook.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'shield'])</span>
                <h3>Integrity</h3>
                <p>We hold ourselves to honest, ethical standards in everything we do.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'star'])</span>
                <h3>Excellence</h3>
                <p>We pursue the highest quality in our training, our people, and our outcomes.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'bolt'])</span>
                <h3>Speed</h3>
                <p>We stay ahead of the curve, embracing new tools and new ways of thinking.</p>
            </div>
            <div class="card about-card">
                <span class="about-ic">@include('partials.icon', ['d' => 'community'])</span>
                <h3>Community</h3>
                <p>We grow stronger together, building a network that supports every member long after graduation.</p>
            </div>
        </div>
    </div>
</section>

{{-- Final CTA --}}
<section class="final-cta">
    <div class="container">
        <h2 class="section-title">Ready to Begin?</h2>
        <p class="section-lead" style="margin-left:auto;margin-right:auto;">Join the movement closing Africa's marketing skills gap, one practical, employable, future-ready marketer at a time.</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">Start Learning →</a>
    </div>
</section>
@endsection
