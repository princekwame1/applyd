@extends('layouts.app')

@section('content')

{{-- Hero --}}
<header class="hero">
    <div class="blob blob-1" aria-hidden="true"></div>
    <div class="blob blob-2" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Applyd Academy Presents</span>
            <h1>Master the Tools.<br><span class="grad-text">Accelerate Your Future.</span></h1>
            <p class="sub">A free, hands-on learning experience. In just 24 days, you'll learn 24 digital tools from expert facilitators across 3 countries.</p>
            <div class="feature-chips">
                <span class="feature-chip"><i>✓</i> Learn through live, practical sessions</span>
                <span class="feature-chip"><i>✓</i> 24 expert facilitators, 3 countries</span>
            </div>
        </div>

        <div class="form-card hero-form" id="register">
            <h2 class="form-title">Reserve Your Free Spot</h2>
            <p class="form-sub">24 days. 24 tools. Completely free. Spots are limited per session.</p>

            @if ($errors->any())
                <div class="error-box">
                    Please fix the highlighted fields below and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" id="regForm">
                @csrf

                <div class="step-meta">Step <span id="stepNow">1</span> of 3</div>
                <h3 class="step-title" id="stepName">About You</h3>
                <div class="step-bar"><span id="stepBarFill" style="width: 33.34%;"></span></div>

                <div class="form-step active" data-step="1" data-title="About You">
                    <div class="form-grid">
                        <div class="full">
                            <label class="field-label" for="full_name">Full Name <span class="req">*</span></label>
                            <input type="text" id="full_name" name="full_name" placeholder="e.g. Ama Mensah" value="{{ old('full_name') }}" required>
                            @error('full_name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full">
                            <label class="field-label" for="gender">Gender <span class="req">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select…</option>
                                @foreach ($genders as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                            @error('gender') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full">
                            <label class="field-label" for="age_range">Age Range <span class="req">*</span></label>
                            <select id="age_range" name="age_range" required>
                                <option value="" disabled {{ old('age_range') ? '' : 'selected' }}>Select…</option>
                                @foreach ($ageRanges as $range)
                                    <option value="{{ $range }}" @selected(old('age_range') === $range)>{{ $range }}</option>
                                @endforeach
                            </select>
                            @error('age_range') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-step" data-step="2" data-title="Contact Details">
                    <div class="form-grid">
                        <div class="full">
                            <label class="field-label" for="country">Country <span class="req">*</span></label>
                            <input type="text" id="country" name="country" placeholder="e.g. Ghana" value="{{ old('country') }}" required>
                            @error('country') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full">
                            <label class="field-label" for="city">City / Region <span class="req">*</span></label>
                            <input type="text" id="city" name="city" placeholder="e.g. Accra" value="{{ old('city') }}" required>
                            @error('city') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full">
                            <label class="field-label" for="phone">Phone Number <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" placeholder="e.g. +233 24 123 4567" value="{{ old('phone') }}" required>
                            @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full">
                            <label class="field-label" for="email">Email Address <span class="req">*</span></label>
                            <input type="email" id="email" name="email" placeholder="e.g. ama@example.com" value="{{ old('email') }}" required>
                            @error('email') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-step" data-step="3" data-title="Your Learning Path">
                    <div class="form-grid">
                        <div class="full">
                            <label class="field-label" for="education">Level of Education <span class="req">*</span></label>
                            <select id="education" name="education" required>
                                <option value="" disabled {{ old('education') ? '' : 'selected' }}>Select…</option>
                                @foreach ($educationLevels as $level)
                                    <option value="{{ $level }}" @selected(old('education') === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                            @error('education') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="full tools-picker">
                            <label class="field-label">Tools You Want to Learn <span class="req">*</span> <small>(select at least one)</small></label>
                            <div class="field-error" id="toolsJsError" hidden>Please select at least one tool you want to learn.</div>
                            @error('tools') <div class="field-error" style="margin-bottom: 8px;">{{ $message }}</div> @enderror
                            @foreach ($toolCategories as $category => $categoryTools)
                                <details {{ $loop->first ? 'open' : '' }}>
                                    <summary>{{ $category }}</summary>
                                    <div class="options">
                                        @foreach ($categoryTools as $tool)
                                            <label class="chk">
                                                <input type="checkbox" name="tools[]" value="{{ $tool }}" @checked(in_array($tool, old('tools', [])))>
                                                {{ $tool }}
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>

                        <div class="full">
                            <label class="chk">
                                <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))>
                                Yes, I'd like to receive updates, tips, and offers from Applyd Academy by email, SMS, or WhatsApp. You can unsubscribe anytime.
                            </label>
                        </div>

                        <div class="full">
                            <p class="consent">By registering, you agree that Applyd Academy may use your information to manage your bootcamp registration, send session reminders, and provide relevant updates. We do not sell your data to third parties.</p>
                        </div>
                    </div>
                </div>

                <div class="form-nav">
                    <button type="button" class="btn btn-outline" id="backBtn" hidden>← Back</button>
                    <button type="button" class="btn btn-brand" id="nextBtn">Next →</button>
                    <button type="submit" class="btn btn-brand" id="submitBtn" hidden>Reserve Your Free Spot →</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container hero-meta">
        <div>
            <span class="meta-label">Format</span>
            <strong>100% Online</strong>
            <small>Live sessions, not recordings</small>
        </div>
        <div>
            <span class="meta-label">Level</span>
            <strong>Beginner-friendly</strong>
            <small>No prior experience needed</small>
        </div>
        <div>
            <span class="meta-label">Schedule</span>
            <strong>Weds &amp; Sats</strong>
            <small>100 minutes per session</small>
        </div>
        <div>
            <span class="meta-label">Cost</span>
            <strong>Completely Free</strong>
            <small>No hidden fees, no upsells</small>
        </div>
        <a href="#register" class="btn btn-primary">Apply Now</a>
    </div>
</header>

{{-- Tool marquee --}}
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        @foreach ([1, 2] as $copy)
            @foreach (collect($toolCategories)->flatten() as $tool)
                <span class="marquee-item">{{ $tool }}</span>
            @endforeach
        @endforeach
    </div>
</div>

{{-- Hidden for now: everything from the About section downwards --}}
@if (false)
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

{{-- Tools You'll Learn --}}
@php
    $shortNames = config('bootcamp.category_short_names');
    $toolCount = $tools->count();
@endphp
<section id="courses">
    <div class="container">
        <div class="center">
            <h2 class="section-title">Your next skill set starts in <span class="hl">24 days</span></h2>
            <p class="section-lead">Pick your tools. Learn the skills that matter. Apply them the same day — with real humans guiding you every step of the way.</p>
            <div class="journey-pills">
                <span class="jp"><i>1</i> Choose your tools</span>
                <span class="jp"><i>2</i> Learn with experts</span>
                <span class="jp"><i>3</i> Apply your skills</span>
            </div>
            <div class="filter-tabs" id="toolTabs">
                <button type="button" class="tab active" data-cat="all">All <em>{{ $toolCount }}</em></button>
                @foreach ($toolCategories as $category => $categoryTools)
                    <button type="button" class="tab" data-cat="{{ Str::slug($category) }}">{{ $shortNames[$category] ?? $category }} <em>{{ count($categoryTools) }}</em></button>
                @endforeach
            </div>
        </div>
        <div class="tool-grid">
            @foreach ($tools as $tool)
                <div class="card tool-item" data-cat="{{ Str::slug($tool->category) }}">
                    <div class="tool-thumb">
                        <img src="{{ $tool->image_url }}" alt="{{ $tool->name }}" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <h3>{{ $tool->name }}</h3>
                        <p>{{ $tool->blurb }}</p>
                        <a href="#register" class="btn btn-brand btn-sm tool-cta">Apply here</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Who Should Attend --}}
<section class="alt">
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
<section>
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
<section class="alt">
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

{{-- Schedule --}}
<section id="schedule">
    <div class="container">
        <h2 class="section-title">Your 24-Day Journey</h2>
        <div class="table-wrap">
            <table class="nice">
                <thead><tr><th>Week</th><th>Focus</th></tr></thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr><td>{{ $schedule->week_label }}</td><td>{{ $schedule->focus }}</td></tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; color:var(--ink-soft);">Schedule coming soon — reserve your spot to be notified first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
        <a href="#register" class="btn btn-primary">Reserve Your Free Spot →</a>
        <p class="micro">No cost. No experience required. Cancel anytime.</p>
    </div>
</section>

{{-- Sticky promo bar --}}
<div class="promo-bar" id="promoBar" hidden>
    <span>🎉 Registration is open — spots are limited per session</span>
    <a href="#register" class="btn btn-primary btn-sm">Reserve Your Free Spot</a>
    <button type="button" class="promo-close" id="promoClose" aria-label="Dismiss">×</button>
</div>
@endif

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24/build/css/intlTelInput.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24/build/js/intlTelInputWithUtils.min.js"></script>
<script>
    $(function () {
        $('#gender, #age_range, #education').each(function () {
            $(this).select2({
                width: '100%',
                placeholder: 'Select…',
            });
        });

        // --- Phone input with inline country/dial-code picker ---
        var phoneEl = document.getElementById('phone');
        var iti = window.intlTelInput(phoneEl, {
            initialCountry: 'gh',
            separateDialCode: true,
        });
        if (phoneEl.value) iti.setNumber(phoneEl.value);

        // --- Multi-step form ---
        var $steps = $('.form-step');
        var total = $steps.length;
        var current = 1;

        function show(n, scroll) {
            current = n;
            var $step = $steps.removeClass('active').filter('[data-step="' + n + '"]').addClass('active');
            $('#backBtn').prop('hidden', n === 1);
            $('#nextBtn').prop('hidden', n === total);
            $('#submitBtn').prop('hidden', n !== total);
            $('#stepNow').text(n);
            $('#stepName').text($step.data('title'));
            $('#stepBarFill').css('width', (n / total * 100) + '%');
            if (scroll) {
                var rect = document.getElementById('register').getBoundingClientRect();
                if (rect.top < 0 || rect.top > window.innerHeight * .6) {
                    document.getElementById('register').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }

        function stepValid(n) {
            var ok = true;
            $steps.filter('[data-step="' + n + '"]').find('input, select').each(function () {
                if (!this.checkValidity()) {
                    this.reportValidity();
                    ok = false;
                    return false;
                }
            });
            return ok;
        }

        // Lock the step area to the tallest step so the card never resizes
        var regForm = document.getElementById('regForm');
        function lockStepHeights() {
            var max = 0;
            $steps.each(function () {
                this.classList.add('measuring');
                max = Math.max(max, this.offsetHeight);
                this.classList.remove('measuring');
            });
            regForm.style.setProperty('--step-h', max + 'px');
        }
        lockStepHeights();
        var resizeTimer;
        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(lockStepHeights, 150);
        });

        $('#nextBtn').on('click', function () {
            if (stepValid(current)) show(current + 1, true);
        });

        $('#backBtn').on('click', function () {
            show(current - 1, true);
        });

        // Enter should advance, not submit, on intermediate steps
        $('#regForm').on('keydown', 'input', function (e) {
            if (e.key === 'Enter' && current !== total) {
                e.preventDefault();
                $('#nextBtn').trigger('click');
            }
        });

        // Require at least one tool before submitting
        $('#regForm').on('submit', function (e) {
            if (!$('input[name="tools[]"]:checked').length) {
                e.preventDefault();
                $('#toolsJsError').prop('hidden', false);
                return;
            }
            phoneEl.value = iti.getNumber();
        });
        $(document).on('change', 'input[name="tools[]"]', function () {
            $('#toolsJsError').prop('hidden', true);
        });

        // After a failed server-side validation, open the first step with an error
        var $firstError = $('.form-step .field-error:visible, .form-step .field-error:not([hidden])').first();
        if ($firstError.length) {
            show($firstError.closest('.form-step').data('step'), false);
        }

        // --- Scroll-reveal animations ---
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var revealEls = document.querySelectorAll(
            'section .card, section .step, section .check-list li, section .section-title, ' +
            'section .section-lead, .split-media, section .table-wrap, .faq details'
        );

        if (!reduceMotion && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            revealEls.forEach(function (el, i) {
                el.classList.add('reveal');
                el.style.transitionDelay = (i % 5) * 70 + 'ms';
                observer.observe(el);
            });
        }

        // --- Tool category filter tabs ---
        $('#toolTabs .tab').on('click', function () {
            $('#toolTabs .tab').removeClass('active');
            $(this).addClass('active');
            var cat = $(this).data('cat');
            $('.tool-item').each(function () {
                var match = cat === 'all' || $(this).data('cat') === cat;
                $(this).toggleClass('tool-hidden', !match);
            });
        });

        // --- Sticky promo bar ---
        var promoBar = document.getElementById('promoBar');
        if (promoBar && !sessionStorage.getItem('promoDismissed')) {
            $(window).on('scroll.promo', function () {
                if (window.scrollY > 700) {
                    promoBar.hidden = false;
                    $(window).off('scroll.promo');
                }
            });
        }
        $('#promoClose').on('click', function () {
            promoBar.hidden = true;
            sessionStorage.setItem('promoDismissed', '1');
        });
    });
</script>
@endpush

@endsection
