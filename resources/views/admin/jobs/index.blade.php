@extends('layout.main')

@section('title', 'Admin — All Jobs')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">All job postings</h1>
                <p class="text-muted mb-0">Remove individual listings to keep the marketplace accurate and trusted.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn head-btn1 mt-2 mt-md-0">Back to dashboard</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Job title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Posted</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                            <tr>
                                <td><strong>{{ $job->title }}</strong></td>
                                <td>{{ $job->company?->company_name ?? $job->company?->name ?? '—' }}</td>
                                <td>{{ $job->location ?? '—' }}</td>
                                <td>{{ $job->created_at->format('M j, Y') }}</td>
                                <td class="text-right">
                                    <form action="{{ route('admin.jobs.destroy', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this job listing?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove job</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">No jobs posted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $jobs->links() }}
        </div>
    </div>
</main>
@endsection
