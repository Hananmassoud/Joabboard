{{-- Public home: route loads latest jobs + favourite IDs for applicants. Uses same job card partial as job board. --}}
@extends('layout.main')
@section('title', 'Home')
@section('content')

<main class="home-page">
    {{-- Top intro + link to full job list --}}
    <section class="home-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9 col-lg-10 text-center">
                    <p class="home-hero-kicker mb-3">Career opportunities</p>
                    <h1 class="home-hero-title mb-4">Find your next opportunity</h1>
                    <p class="home-hero-lead mb-4">
                        Browse roles from verified employers and apply in a few steps.
                    </p>
                    <div class="home-hero-actions">
                        <a href="{{ route('jobs.index') }}" class="btn head-btn2 home-hero-btn-primary">Browse all jobs</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Short list of recent jobs (not paginated); “View all” goes to /jobs --}}
    <section class="home-jobs-section">
        <div class="container">
            @if (session('status'))
                <div class="alert alert-success mb-4 border-0 shadow-sm">{{ session('status') }}</div>
            @endif
            <div class="row align-items-end mb-4 mb-lg-5">
                <div class="col-lg-8">
                    <h2 class="home-section-title mb-2">Latest openings</h2>
                    <p class="home-section-subtitle mb-0">Recently posted positions from our network.</p>
                </div>
                <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                    <a href="{{ route('jobs.index') }}" class="home-link-all">View all jobs &rarr;</a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-xl-10">
                    @php
                        $homeJobs = $jobs ?? collect();
                    @endphp

                    <div class="home-jobs-list">
                    @forelse ($homeJobs as $job)
                        {{-- Card includes save/favourite for logged-in applicants --}}
                        @include('partials.job-card', [
                            'job' => $job,
                            'favouriteJobIds' => $favouriteJobIds ?? [],
                            'cardClass' => 'home-job-card',
                        ])
                    @empty
                        <div class="home-empty-state text-center py-5">
                            <p class="text-muted mb-4">No jobs posted yet. Check back soon.</p>
                            @guest
                                <a href="{{ route('register') }}" class="btn head-btn2">Register as a company</a>
                            @endguest
                        </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

@endsection
