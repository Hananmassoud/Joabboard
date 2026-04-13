{{-- Reusable job card for home page and /jobs listing. Pass $job, optional $favouriteJobIds (applicant saved jobs), optional $cardClass. --}}
@php
    $companyName = $job->company?->company_name ?? $job->company?->name ?? 'Company';
    $favIds = $favouriteJobIds ?? [];
    $isSaved = in_array($job->id, $favIds);
@endphp
<article class="single-job-items job-card-pro job-card-pro--listing {{ $cardClass ?? '' }}">
    <div class="job-card-pro__main">
        <a href="{{ route('jobs.show', $job) }}" class="company-img company-logo-sm job-card-pro__logo" aria-label="View {{ $job->title }}">
            @php
                $jobLogo = $job->company?->company_logo;
            @endphp
            <img src="{{ $jobLogo ? asset('storage/' . $jobLogo) : asset('assets/img/icon/job-list1.png') }}"
                 alt=""
                 width="80"
                 height="80"
                 loading="lazy"
                 decoding="async">
        </a>
        <div class="job-card-pro__body">
            <h3 class="job-card-pro__title">
                <a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a>
            </h3>
            <div class="job-card-pro__meta">
                <span class="job-card-pro__company">{{ $companyName }}</span>
                @if($job->location)
                    <span class="job-card-pro__meta-sep" aria-hidden="true">·</span>
                    <span class="job-card-pro__location" title="{{ $job->location }}">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span class="job-card-pro__location-text">{{ $job->location }}</span>
                    </span>
                @endif
                @if($job->salary)
                    <span class="job-card-pro__meta-sep" aria-hidden="true">·</span>
                    <span class="job-card-pro__salary">{{ $job->salary }}</span>
                @endif
            </div>
        </div>
    </div>
    <footer class="job-card-pro__footer">
        <div class="job-card-pro__footer-start">
            @include('partials.job-favourite-button', [
                'job' => $job,
                'isSaved' => $isSaved,
            ])
            @if($job->job_type)
                <a href="{{ route('jobs.show', $job) }}" class="job-card-pro__type-pill">{{ $job->job_type }}</a>
            @else
                <a href="{{ route('jobs.show', $job) }}" class="job-card-pro__type-pill job-card-pro__type-pill--ghost">View role</a>
            @endif
        </div>
        <div class="job-card-pro__footer-end">
            <time class="job-card-pro__posted" datetime="{{ $job->created_at->toIso8601String() }}">{{ $job->created_at->diffForHumans() }}</time>
        </div>
    </footer>
</article>
