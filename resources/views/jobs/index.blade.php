{{-- Public job board: paginated list. JobController@index passes $jobs and optional $favouriteJobIds for logged-in applicants. --}}
@extends('layout.main')

@section('title', 'Jobs')

@section('content')
<main class="jobs-board-page jb-panel-page">
    <div class="container">
        <div class="row mb-4 mb-lg-5">
            <div class="col-lg-12 text-center">
                <div class="section-tittle">
                    <h2>Available Jobs</h2>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10">
                {{-- Flash after save job / apply success, etc. --}}
                @if (session('status'))
                    <div class="alert alert-success mb-4 border-0 shadow-sm">{{ session('status') }}</div>
                @endif
                <div class="jobs-board-list">
                @forelse ($jobs as $job)
                    {{-- Shared card layout: title, company, save/favourite button --}}
                    @include('partials.job-card', [
                        'job' => $job,
                        'favouriteJobIds' => $favouriteJobIds ?? [],
                    ])
                @empty
                    <p class="text-center text-muted py-4 mb-0">No jobs available yet.</p>
                @endforelse
                </div>

                {{-- Laravel pagination links (Bootstrap style if configured) --}}
                <div class="mt-4 pt-2">
                    {{ $jobs->links() }}
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

