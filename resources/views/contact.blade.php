@extends('layout.main')

@section('title', 'Contact Us')

@section('content')
<main class="contact-page">
    <section class="contact-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-xl-8 col-lg-9">
                    <p class="contact-kicker mb-3">Get in touch</p>
                    <h1 class="contact-title mb-4">We would love to hear from you</h1>
                    <p class="contact-lead mb-0">
                        Questions about hiring, applying, or using the platform? Send us a message and our team will respond as soon as possible.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="contact-info-card mb-4">
                        <h3 class="contact-info-title mb-4">Contact details</h3>
                        <ul class="contact-info-list mb-0">
                            <li>
                                <span class="contact-info-label">Email</span>
                                <a href="mailto:support@jobboard.com">support@jobboard.com</a>
                            </li>
                            <li>
                                <span class="contact-info-label">Phone</span>
                                <a href="tel:+92987654321">+92 987 654 321</a>
                            </li>
                            <li>
                                <span class="contact-info-label">Location</span>
                                <span>Lahore, Pakistan</span>
                            </li>
                            <li>
                                <span class="contact-info-label">Hours</span>
                                <span>Monday – Friday, 9:00 – 18:00</span>
                            </li>
                        </ul>
                    </div>
                    <div class="contact-note">
                        <strong>Applicants &amp; employers</strong>
                        <p class="mb-0">Use the form for general inquiries. For account issues, include your registered email.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="contact-form-card">
                        <h3 class="contact-form-title mb-4">Send a message</h3>

                        @if (session('status'))
                            <div class="alert alert-success contact-alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('contact.store') }}" class="contact-form" novalidate>
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="contact_name">Full name</label>
                                    <input type="text" name="name" id="contact_name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" required autocomplete="name">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="contact_email">Email</label>
                                    <input type="email" name="email" id="contact_email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" required autocomplete="email">
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="contact_subject">Subject</label>
                                <input type="text" name="subject" id="contact_subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject') }}" required maxlength="120" placeholder="How can we help?">
                                @error('subject')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-4">
                                <label for="contact_message">Message</label>
                                <textarea name="message" id="contact_message" rows="6" class="form-control @error('message') is-invalid @enderror"
                                          required placeholder="Write your message here...">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn head-btn2 btn-contact-submit">Send message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
