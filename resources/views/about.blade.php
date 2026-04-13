@extends('layout.main')

@section('title', 'About Us')

@section('content')
<main class="about-page">
    <section class="about-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-xl-9 col-lg-10">
                    <p class="about-kicker mb-3">About Job Board</p>
                    <h1 class="about-title mb-4">Where talent and employers meet</h1>
                    <p class="about-lead mb-0">
                        A modern hiring platform built to make discovery, applications, and review simple—for applicants who want clarity and companies who need efficiency.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-body">
        <div class="container about-container">
            <div class="row about-mission-row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <article class="about-card about-card--lift">
                        <span class="about-card-icon" aria-hidden="true"><i class="fas fa-bullseye"></i></span>
                        <h2 class="about-card-title">Our mission</h2>
                        <p class="about-card-text mb-0">
                            We help people find meaningful work and help teams hire with confidence. That means transparent listings, fair application flows, and tools that keep everyone on the same page—from first click to shortlist.
                        </p>
                    </article>
                </div>
                <div class="col-lg-6">
                    <article class="about-card about-card--lift">
                        <span class="about-card-icon" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
                        <h2 class="about-card-title">What we offer</h2>
                        <ul class="about-checklist mb-0">
                            <li><i class="fas fa-check" aria-hidden="true"></i> Separate workspaces for applicants and companies</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Dashboards to track saved jobs, applications, and postings</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Structured job posts and simple apply flows</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Tools for employers to review and compare candidates</li>
                        </ul>
                    </article>
                </div>
            </div>

            <div class="about-highlights-head text-center">
                <h2 class="about-section-heading mb-2">Why teams use us</h2>
                <p class="about-section-lead mb-0">Built around how hiring actually works—fast review, clear signals, less friction.</p>
            </div>

            <div class="row about-highlights">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="about-highlight-card">
                        <span class="about-card-icon about-card-icon--centered" aria-hidden="true"><i class="fas fa-bolt"></i></span>
                        <h3 class="about-highlight-title">Speed</h3>
                        <p class="about-highlight-text mb-0">Post roles and review applicants from one organized pipeline—no scattered spreadsheets or inboxes.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="about-highlight-card">
                        <span class="about-card-icon about-card-icon--centered" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
                        <h3 class="about-highlight-title">Clarity</h3>
                        <p class="about-highlight-text mb-0">Role-based accounts and structured profiles so everyone knows who they’re talking to.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-highlight-card">
                        <span class="about-card-icon about-card-icon--centered" aria-hidden="true"><i class="fas fa-tasks"></i></span>
                        <h3 class="about-highlight-title">Workflow</h3>
                        <p class="about-highlight-text mb-0">From listing to application to review—each step stays simple and traceable.</p>
                    </div>
                </div>
            </div>

            <div class="about-cta">
                <div class="about-cta-inner text-center">
                    <h2 class="about-cta-title mb-2">Ready to get started?</h2>
                    <p class="about-cta-lead mb-4">Browse open roles or create an account to post jobs and manage applications.</p>
                    <div class="about-cta-actions">
                        <a href="{{ route('jobs.index') }}" class="btn head-btn2 about-cta-btn">Browse jobs</a>
                        @guest
                            <a href="{{ route('register') }}" class="btn head-btn1 about-cta-btn">Create account</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn head-btn2 jb-auth-btn jb-auth-btn--invert about-cta-btn">Go to dashboard</a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
