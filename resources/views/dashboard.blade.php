{{-- Applicant dashboard: saved jobs + recent applications. Data comes from routes/web.php closure. Companies see a short redirect message (they use Company Panel). --}}
@extends('layout.main')

@section('title', 'Dashboard')

@section('content')
    <main class="applicant-dashboard-page">
        <div class="container jb-dashboard-container">
            {{-- Rare edge case if a company hits /dashboard directly --}}
            @if (auth()->user()->isCompany())
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card auth-card p-4 p-md-5">
                            <h2 class="mb-3">Dashboard</h2>
                            <p class="text-muted mb-4">You are logged in as a company.</p>
                            <a href="{{ route('company.jobs.index') }}" class="btn head-btn2">Go to Company Panel</a>
                        </div>
                    </div>
                </div>
            @else
                {{-- Variables are passed from the route: favourites list, application counts, recent applications --}}
                @php
                    $dashboardJobs = $jobs ?? collect();
                    $recentApps = $recentApplications ?? collect();
                    $jobsCount = $availableJobsCount ?? 0;
                    $myApplications = $appliedJobsCount ?? 0;
                    $pendingCount = $pendingApplicationsCount ?? 0;
                    $firstName = explode(' ', trim(auth()->user()->name))[0];
                @endphp

                {{-- Top bar: identity + primary CTA --}}
                <div class="applicant-dash-header mb-4 mb-lg-5">
                    <div class="applicant-dash-header-inner">
                        <div class="applicant-dash-identity">
                            <div class="applicant-dash-avatar" aria-hidden="true">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="applicant-dash-kicker mb-1">Applicant workspace</p>
                                <h1 class="applicant-dash-title mb-2">Welcome back, {{ $firstName }}</h1>
                                <p class="applicant-dash-sub mb-0">
                                    Your saved jobs appear here. Browse the home page or job board and use <strong>Save job</strong> to add roles to this list.
                                </p>
                            </div>
                        </div>
                        <div class="applicant-dash-actions">
                            <a href="{{ route('home') }}" class="btn head-btn2 applicant-dash-btn-primary">Browse home &amp; jobs</a>
                            <a href="{{ route('contact') }}" class="btn head-btn1 applicant-dash-btn-secondary">Get support</a>
                        </div>
                    </div>
                </div>

                {{-- KPI row --}}
                <div class="row applicant-dash-stats mb-4 mb-lg-5">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="applicant-stat-tile">
                            <div class="applicant-stat-icon applicant-stat-icon--jobs"><i class="fas fa-briefcase"></i></div>
                            <div>
                                <p class="applicant-stat-label">Saved jobs</p>
                                <p class="applicant-stat-value">{{ $jobsCount }}</p>
                                <p class="applicant-stat-hint">Jobs you saved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="applicant-stat-tile">
                            <div class="applicant-stat-icon applicant-stat-icon--apps"><i class="fas fa-paper-plane"></i></div>
                            <div>
                                <p class="applicant-stat-label">Applications</p>
                                <p class="applicant-stat-value">{{ $myApplications }}</p>
                                <p class="applicant-stat-hint">Total submissions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="applicant-stat-tile">
                            <div class="applicant-stat-icon applicant-stat-icon--pending"><i class="fas fa-hourglass-half"></i></div>
                            <div>
                                <p class="applicant-stat-label">Pending review</p>
                                <p class="applicant-stat-value">{{ $pendingCount }}</p>
                                <p class="applicant-stat-hint">Awaiting employer response</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Latest jobs --}}
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <div class="applicant-dash-panel">
                            <div class="applicant-dash-panel-head">
                                <div>
                                    <h2 class="applicant-dash-panel-title mb-1">Saved jobs</h2>
                                    <p class="applicant-dash-panel-desc mb-0">Only jobs you saved from the home page or job listing appear here.</p>
                                </div>
                                <a href="{{ route('home') }}" class="applicant-dash-panel-link">Go to home</a>
                            </div>

                            <div class="applicant-dash-job-list">
                                {{-- Each row is a job the applicant saved (favourite), not every job on the site --}}
                                @forelse ($dashboardJobs as $job)
                                    @php
                                        $jobLogo = $job->company?->company_logo;
                                    @endphp
                                    <article class="applicant-job-row">
                                        <div class="applicant-job-row-logo company-logo-sm">
                                            <a href="{{ route('jobs.show', $job) }}">
                                                <img src="{{ $jobLogo ? asset('storage/' . $jobLogo) : asset('assets/img/icon/job-list1.png') }}"
                                                     alt=""
                                                     width="56"
                                                     height="56"
                                                     loading="lazy"
                                                     decoding="async">
                                            </a>
                                        </div>
                                        <div class="applicant-job-row-body">
                                            <h3 class="applicant-job-title">
                                                <a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a>
                                            </h3>
                                            <p class="applicant-job-company mb-1">
                                                {{ $job->company?->company_name ?? $job->company?->name ?? 'Company' }}
                                            </p>
                                            <p class="applicant-job-meta mb-0">
                                                <span>@if($job->location)<i class="fas fa-map-marker-alt mr-1"></i>{{ $job->location }}@else <span class="text-muted">Location not specified</span>@endif</span>
                                                @if($job->salary)<span class="applicant-job-meta-sep">·</span><span>{{ $job->salary }}</span>@endif
                                                <span class="applicant-job-meta-sep">·</span>
                                                <span>{{ $job->created_at->diffForHumans() }}</span>
                                            </p>
                                        </div>
                                        <div class="applicant-job-row-cta">
                                            <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm head-btn1">View</a>
                                        </div>
                                    </article>
                                @empty
                                    <div class="applicant-dash-empty">
                                        <i class="fas fa-heart applicant-dash-empty-icon"></i>
                                        <p class="mb-2"><strong>No saved jobs yet</strong></p>
                                        <p class="text-muted small mb-0">Open the home page or job board and click <strong>Save job</strong> on roles you like.</p>
                                        <a href="{{ route('home') }}" class="btn head-btn2 btn-sm mt-3">Go to home</a>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Applications --}}
                    <div class="col-lg-5">
                        <div class="applicant-dash-panel applicant-dash-panel--accent h-100">
                            <div class="applicant-dash-panel-head">
                                <div>
                                    <h2 class="applicant-dash-panel-title mb-1">Your applications</h2>
                                    <p class="applicant-dash-panel-desc mb-0">Status of your recent submissions.</p>
                                </div>
                            </div>

                            @if($recentApps->isEmpty())
                                <div class="applicant-dash-empty applicant-dash-empty--compact">
                                    <p class="mb-2 text-muted">You have not applied for any job yet.</p>
                                    <a href="{{ route('jobs.index') }}" class="btn head-btn2 btn-sm">Find a job</a>
                                </div>
                            @else
                                <ul class="applicant-app-list mb-0">
                                    @foreach($recentApps as $application)
                                        <li class="applicant-app-item">
                                            <div class="applicant-app-item-main">
                                                <p class="applicant-app-job mb-1">
                                                    @if($application->job)
                                                        <a href="{{ route('jobs.show', $application->job) }}">{{ $application->job->title }}</a>
                                                    @else
                                                        <span class="text-muted">Job unavailable</span>
                                                    @endif
                                                </p>
                                                <p class="applicant-app-co mb-0">
                                                    {{ $application->job?->company?->company_name ?? $application->job?->company?->name ?? '—' }}
                                                </p>
                                            </div>
                                            <div class="applicant-app-item-meta text-md-right">
                                                <span class="app-status app-status-{{ strtolower($application->status) }}">{{ ucfirst($application->status) }}</span>
                                                <p class="applicant-app-time mb-0">{{ $application->created_at->diffForHumans() }}</p>
                                            </div>
                                            <div class="applicant-app-item-review w-100 mt-2 pt-2 border-top border-light">
                                                @if($application->company_read_at)
                                                    <p class="applicant-app-reviewed mb-0">
                                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                        Your application has been reviewed by the employer.
                                                        <span class="d-block small mt-1 opacity-90">{{ $application->company_read_at->format('M j, Y g:i A') }}</span>
                                                    </p>
                                                @else
                                                    <p class="applicant-app-awaiting mb-0">Awaiting employer review</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="text-center mt-3 pt-2 border-top border-light">
                                    <a href="{{ route('jobs.index') }}" class="small font-weight-bold">Explore more jobs →</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
