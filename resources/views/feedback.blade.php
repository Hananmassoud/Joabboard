@extends('layout.main')

@section('title', 'Feedback')

@section('content')
<main class="feedback-page">
    <section class="contact-hero feedback-page-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-xl-8 col-lg-9">
                    <p class="contact-kicker mb-3">Help us improve</p>
                    <h1 class="contact-title mb-4">Share your feedback</h1>
                    <p class="contact-lead mb-0">
                        Tell us what works, what doesn’t, and what you’d like to see next. Every submission is read by our team.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section feedback-page-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="contact-info-card feedback-aside-card">
                        <h3 class="contact-info-title mb-4">Why it matters</h3>
                        <ul class="feedback-aside-list list-unstyled mb-0">
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Reviewed by our team</strong>
                                    <p class="feedback-aside-item-text mb-0">Bug reports and ideas are triaged and tracked—we don’t use this form for marketing.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-lightbulb"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Shape the product</strong>
                                    <p class="feedback-aside-item-text mb-0">Feature requests and UX suggestions directly influence what we build next.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Optional rating</strong>
                                    <p class="feedback-aside-item-text mb-0">A quick 1–5 score helps us see overall satisfaction at a glance.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-reply"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Follow-up</strong>
                                    <p class="feedback-aside-item-text mb-0">Add your email if you’re open to a reply; it stays private to support.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Response time</strong>
                                    <p class="feedback-aside-item-text mb-0">We review submissions regularly and prioritize urgent bugs and account-impacting issues first.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Safe & secure</strong>
                                    <p class="feedback-aside-item-text mb-0">Your feedback is handled by support only and never shared publicly.</p>
                                </div>
                            </li>
                            <li>
                                <span class="feedback-aside-icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                                <div>
                                    <strong class="feedback-aside-item-title">Community-driven</strong>
                                    <p class="feedback-aside-item-text mb-0">Suggestions from applicants and companies help us prioritize what matters most.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="feedback-privacy-note mt-3">
                        <strong>Privacy</strong>
                        <p class="mb-0">Don’t include passwords or sensitive personal data. For account-specific issues, use the email you registered with.</p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form-card feedback-form-card">
                        <div class="feedback-form-header">
                            <h3 class="contact-form-title mb-2">Your feedback</h3>
                            <p class="feedback-form-intro mb-0">Required fields are marked. Everything else helps us respond faster.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success contact-alert feedback-alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('feedback.store') }}" class="contact-form feedback-form" novalidate>
                            @csrf

                            <div class="feedback-form-section">
                                <h4 class="feedback-form-section-title">Feedback details</h4>

                                <div class="row feedback-form-grid">
                                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                                        <div class="feedback-field">
                                            <label for="feedback_type">Type <span class="text-danger">*</span></label>
                                            <select name="type" id="feedback_type" class="form-control feedback-form-select @error('type') is-invalid @enderror" required>
                                                @php($selectedType = old('type', 'general'))
                                                <option value="general" @selected($selectedType === 'general')>General</option>
                                                <option value="bug" @selected($selectedType === 'bug')>Bug report</option>
                                                <option value="feature" @selected($selectedType === 'feature')>Feature request</option>
                                                <option value="content" @selected($selectedType === 'content')>Content issue</option>
                                                <option value="other" @selected($selectedType === 'other')>Other</option>
                                            </select>
                                            @error('type')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="feedback-field">
                                            <label for="feedback_rating">Experience rating</label>
                                            <select name="rating" id="feedback_rating" class="form-control feedback-form-select @error('rating') is-invalid @enderror" aria-describedby="feedback_rating_hint">
                                                <option value="">No rating</option>
                                                @foreach([
                                                    5 => '5 — Excellent',
                                                    4 => '4 — Good',
                                                    3 => '3 — Okay',
                                                    2 => '2 — Poor',
                                                    1 => '1 — Very poor',
                                                ] as $ratingValue => $ratingLabel)
                                                    <option value="{{ $ratingValue }}" @selected((string) old('rating') === (string) $ratingValue)>{{ $ratingLabel }}</option>
                                                @endforeach
                                            </select>
                                            <p id="feedback_rating_hint" class="feedback-field-hint mb-0">Optional. Helps us track satisfaction over time.</p>
                                            @error('rating')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="feedback-field">
                                    <label for="feedback_subject">Subject</label>
                                    <input type="text" name="subject" id="feedback_subject" class="form-control @error('subject') is-invalid @enderror"
                                           value="{{ old('subject') }}" maxlength="160" placeholder="Short summary (optional)">
                                    @error('subject')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="feedback-field feedback-field--message mb-0">
                                    <label for="feedback_message">Message <span class="text-danger">*</span></label>
                                    <textarea name="message" id="feedback_message" rows="7" class="form-control @error('message') is-invalid @enderror"
                                              required placeholder="Describe the issue, idea, or suggestion in as much detail as you can.">{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="feedback-form-section feedback-form-section--contact">
                                <h4 class="feedback-form-section-title">Your details</h4>
                                <p class="feedback-form-section-hint">Optional. We only use this to follow up if needed.</p>

                                <div class="row feedback-form-grid">
                                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                                        <div class="feedback-field">
                                            <label for="feedback_name">Name</label>
                                            <input type="text" name="name" id="feedback_name" class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name', auth()->check() ? auth()->user()->name : '') }}" autocomplete="name" placeholder="Your name">
                                            @error('name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="feedback-field mb-0">
                                            <label for="feedback_email">Email</label>
                                            <input type="email" name="email" id="feedback_email" class="form-control @error('email') is-invalid @enderror"
                                                   value="{{ old('email', auth()->check() ? auth()->user()->email : '') }}" autocomplete="email" placeholder="you@example.com">
                                            @error('email')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="feedback-honeypot" aria-hidden="true">

                            <div class="feedback-form-actions">
                                <button type="submit" class="btn head-btn2 btn-contact-submit feedback-submit-btn">
                                    <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>Submit feedback
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
