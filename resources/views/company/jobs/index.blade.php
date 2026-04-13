{{-- Company home dashboard after login: profile hero, stats, notifications, job list with delete. Data from CompanyJobController@index. --}}
@extends('layout.main')

@section('title', 'Company Dashboard')

@section('content')
<main class="company-dashboard-page">
    <div class="container jb-dashboard-container">
        {{-- Local variables for counts and display strings used in this view only --}}
        @php
            $jobsCount = $jobs->count();
            $jobsWithApplications = $jobs->where('applications_count', '>', 0)->count();
            $recentApplications = $recentApplications ?? collect();
            $unreadApplicationsCount = $unreadApplicationsCount ?? 0;
            $totalApplicationsCount = $totalApplicationsCount ?? 0;
            $logoPath = auth()->user()->company_logo ?? null;
            $companyDisplay = auth()->user()->company_name ?? auth()->user()->name;
            $firstName = explode(' ', trim(auth()->user()->name))[0];
        @endphp

        @if (session('status'))
            <div class="alert alert-success company-dash-alert mb-4">{{ session('status') }}</div>
        @endif

        {{-- Top banner: logo, company name, quick actions --}}
        <div class="company-dash-hero mb-4 mb-lg-5">
            <div class="company-dash-hero-inner">
                <div class="company-dash-hero-brand">
                    <div class="company-dash-logo-wrap">
                        @if($logoPath)
                            <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $companyDisplay }} logo" class="company-dash-logo" loading="lazy" decoding="async">
                        @else
                            <div class="company-dash-logo-fallback" aria-hidden="true">
                                <i class="fas fa-building"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="company-dash-kicker mb-1">Employer workspace</p>
                        <h1 class="company-dash-title mb-2">{{ $companyDisplay }}</h1>
                        <p class="company-dash-sub mb-0">
                            Hi {{ $firstName }} — post roles, review applicants, and keep your hiring pipeline organized.
                        </p>
                    </div>
                </div>
                <div class="company-dash-hero-actions">
                    <a href="{{ route('company.jobs.create') }}" class="btn head-btn2 company-dash-btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i> Post new job
                    </a>
                    <a href="{{ route('jobs.index') }}" class="btn head-btn1 company-dash-btn-secondary" target="_blank" rel="noopener">View public job board</a>
                </div>
            </div>

            <dl class="company-dash-meta row mb-0 mt-4 pt-4 company-dash-meta-border">
                <div class="col-md-4 mb-2 mb-md-0">
                    <dt class="company-dash-meta-label">Contact email</dt>
                    <dd class="company-dash-meta-value mb-0">{{ auth()->user()->email }}</dd>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <dt class="company-dash-meta-label">Established</dt>
                    <dd class="company-dash-meta-value mb-0">{{ auth()->user()->company_established_year ?? auth()->user()->created_at?->format('Y') }}</dd>
                </div>
                <div class="col-md-4">
                    <dt class="company-dash-meta-label">Owner</dt>
                    <dd class="company-dash-meta-value mb-0">{{ auth()->user()->name }}</dd>
                </div>
            </dl>
            @if(auth()->user()->company_description)
                <p class="company-dash-about mb-0 mt-3 pt-3 company-dash-meta-border">
                    <span class="company-dash-about-label">About</span>
                    {{ auth()->user()->company_description }}
                </p>
            @endif
        </div>

        {{-- Simple numbers: jobs posted, applications totals --}}
        <div class="row company-dash-stats mb-4 mb-lg-5">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="company-stat-tile">
                    <div class="company-stat-icon company-stat-icon--jobs"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <p class="company-stat-label">Active listings</p>
                        <p class="company-stat-value">{{ $jobsCount }}</p>
                        <p class="company-stat-hint">Jobs you have posted</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="company-stat-tile">
                    <div class="company-stat-icon company-stat-icon--apps"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="company-stat-label">Total applications</p>
                        <p class="company-stat-value">{{ $totalApplicationsCount }}</p>
                        <p class="company-stat-hint">Across all roles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="company-stat-tile">
                    <div class="company-stat-icon company-stat-icon--hiring"><i class="fas fa-briefcase"></i></div>
                    <div>
                        <p class="company-stat-label">Roles with applicants</p>
                        <p class="company-stat-value">{{ $jobsWithApplications }}</p>
                        <p class="company-stat-hint">Jobs receiving interest</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Recent applications list + mark-all-read --}}
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="company-dash-panel h-100">
                    <div class="company-dash-panel-head">
                        <div>
                            <h2 class="company-dash-panel-title mb-1">Application activity</h2>
                            <p class="company-dash-panel-desc mb-0">Recent submissions to your openings.</p>
                        </div>
                        @if($unreadApplicationsCount > 0)
                            <form method="POST" action="{{ route('company.notifications.read') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-sm head-btn1">Mark all read</button>
                            </form>
                        @endif
                    </div>
                    <p class="company-dash-notify-count mb-3">
                        <span class="company-dash-badge">{{ $unreadApplicationsCount }}</span>
                        new notification(s)
                    </p>
                    <div class="company-notify-list">
                        @forelse($recentApplications as $application)
                            <div class="company-notify-item">
                                <div>
                                    <p class="company-notify-name mb-1">{{ $application->applicant?->name ?? 'Applicant' }}</p>
                                    <p class="company-notify-job mb-0">
                                        @if($application->job)
                                            <a href="{{ route('company.jobs.applications', $application->job) }}">{{ $application->job->title }}</a>
                                        @else
                                            <span class="text-muted">Job unavailable</span>
                                        @endif
                                    </p>
                                    <p class="company-notify-time mb-0">{{ $application->created_at->diffForHumans() }}</p>
                                </div>
                                @if(auth()->user()->company_notifications_last_seen_at && $application->created_at->gt(auth()->user()->company_notifications_last_seen_at))
                                    <span class="app-status app-status-pending">New</span>
                                @endif
                            </div>
                        @empty
                            <div class="company-dash-empty">
                                <i class="fas fa-inbox company-dash-empty-icon"></i>
                                <p class="text-muted mb-0">No applications yet. Post a job to start receiving candidates.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Snapshot of the most recent applicant --}}
            <div class="col-lg-6">
                <div class="company-dash-panel company-dash-panel--accent h-100">
                    <div class="company-dash-panel-head">
                        <div>
                            <h2 class="company-dash-panel-title mb-1">Latest applicant</h2>
                            <p class="company-dash-panel-desc mb-0">Most recent submission details.</p>
                        </div>
                    </div>
                    @php $latestApplication = $recentApplications->first(); @endphp
                    @if($latestApplication)
                        <div class="company-latest-applicant">
                            <div class="company-latest-row">
                                <span class="company-latest-label">Name</span>
                                <span class="company-latest-value">{{ $latestApplication->applicant?->name ?? '—' }}</span>
                            </div>
                            <div class="company-latest-row">
                                <span class="company-latest-label">Email</span>
                                <span class="company-latest-value"><a href="mailto:{{ $latestApplication->applicant?->email }}">{{ $latestApplication->applicant?->email ?? '—' }}</a></span>
                            </div>
                            <div class="company-latest-row">
                                <span class="company-latest-label">Role</span>
                                <span class="company-latest-value">
                                    @if($latestApplication->job)
                                        <a href="{{ route('company.jobs.applications', $latestApplication->job) }}">{{ $latestApplication->job->title }}</a>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="company-latest-cover">
                                <span class="company-latest-label d-block mb-2">Cover letter</span>
                                <p class="company-latest-cover-text mb-0">{{ $latestApplication->cover_letter ?: 'No cover letter submitted.' }}</p>
                            </div>
                            <div class="auth-inline-actions mt-3">
                                @if($latestApplication->cv_url)
                                    <a class="btn btn-sm head-btn1" href="{{ $latestApplication->cv_url }}" target="_blank" rel="noopener">Open CV link</a>
                                @endif
                                @if($latestApplication->cv_path)
                                    <a class="btn btn-sm head-btn1" href="{{ asset('storage/' . $latestApplication->cv_path) }}" target="_blank" rel="noopener">Download CV</a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="company-dash-empty company-dash-empty--compact">
                            <p class="text-muted mb-0">Applicant details will show here after the first application.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Each row links to applications for that job; delete uses POST with spoofed DELETE method --}}
        <div class="company-dash-panel company-dash-panel--wide mt-4">
            <div class="company-dash-panel-head">
                <div>
                    <h2 class="company-dash-panel-title mb-1">Your job listings</h2>
                    <p class="company-dash-panel-desc mb-0">Manage postings and review applications per role.</p>
                </div>
                <span class="company-dash-count-pill">{{ $jobsCount }} {{ $jobsCount === 1 ? 'job' : 'jobs' }}</span>
            </div>

            @forelse ($jobs as $job)
                <div class="company-job-row">
                    <div class="company-job-row-main">
                        <h3 class="company-job-title mb-1">{{ $job->title }}</h3>
                        <p class="company-job-meta mb-0">
                            @if($job->location)<i class="fas fa-map-marker-alt mr-1"></i>{{ $job->location }}@else<span class="text-muted">Location not set</span>@endif
                            @if($job->salary)<span class="company-job-meta-sep">·</span>{{ $job->salary }}@endif
                            <span class="company-job-meta-sep">·</span>Posted {{ $job->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="company-job-row-actions">
                        <a href="{{ route('company.jobs.applications', $job) }}" class="btn jb-btn jb-btn--ghost company-job-action-btn">
                            Applications <span class="badge badge-light ml-1">{{ $job->applications_count }}</span>
                        </a>
                        <form method="POST" action="{{ route('company.jobs.destroy', $job) }}" class="d-inline" onsubmit="return confirm('Delete this job? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn jb-btn jb-btn--secondary company-job-action-btn">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="company-dash-empty text-center py-4">
                    <i class="fas fa-clipboard-list company-dash-empty-icon mb-2"></i>
                    <p class="mb-3 text-muted">You have not posted any jobs yet.</p>
                    <a href="{{ route('company.jobs.create') }}" class="btn head-btn2">Create your first job</a>
                </div>
            @endforelse
        </div>
    </div>
</main>
@endsection
