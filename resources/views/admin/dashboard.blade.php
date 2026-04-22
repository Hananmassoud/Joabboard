@extends('layout.main')

@section('title', 'Admin — Dashboard')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Administration</h1>
                <p class="text-muted mb-0">Overview of platform activity and quick access to management tools.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row mb-4">
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--companies">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Companies</span>
                        <span class="admin-stat-value">{{ $stats['companies'] }}</span>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-building"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--applicants">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Applicants</span>
                        <span class="admin-stat-value">{{ $stats['applicants'] }}</span>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--jobs">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Jobs posted</span>
                        <span class="admin-stat-value">{{ $stats['jobs'] }}</span>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-briefcase"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--applications">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Applications</span>
                        <span class="admin-stat-value">{{ $stats['applications'] }}</span>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-file-alt"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--contacts">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Contact messages</span>
                        <div class="d-flex align-items-baseline flex-wrap">
                            <span class="admin-stat-value">{{ $stats['contact_messages'] }}</span>
                            @if(($stats['contact_unread'] ?? 0) > 0)
                                <span class="badge badge-primary admin-badge-new ml-2">{{ $stats['contact_unread'] }} new</span>
                            @endif
                        </div>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-envelope"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="admin-stat-card admin-stat-card--feedback">
                    <div class="admin-stat-main">
                        <span class="admin-stat-label">Feedback</span>
                        <div class="d-flex align-items-baseline flex-wrap">
                            <span class="admin-stat-value">{{ $stats['feedback'] ?? 0 }}</span>
                            @if(($stats['feedback_unread'] ?? 0) > 0)
                                <span class="badge badge-primary admin-badge-new ml-2">{{ $stats['feedback_unread'] }} new</span>
                            @endif
                        </div>
                    </div>
                    <div class="admin-stat-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="admin-card-title mb-0">Recent jobs</h2>
                        <a href="{{ route('admin.jobs.index') }}" class="admin-link">Manage all jobs</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Company</th>
                                    <th>Posted</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentJobs as $job)
                                    <tr>
                                        <td><strong>{{ $job->title }}</strong></td>
                                        <td>{{ $job->company?->company_name ?? $job->company?->name ?? '—' }}</td>
                                        <td>{{ $job->created_at->format('M j, Y') }}</td>
                                        <td class="text-right">
                                            <form action="{{ route('admin.jobs.destroy', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this job from the platform?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No jobs yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row jb-stack-row">
            <div class="col-lg-12">
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="admin-card-title mb-0">Recent contact messages</h2>
                        <a href="{{ route('admin.contacts.index') }}" class="admin-link">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentContacts as $c)
                                    <tr class="{{ $c->isUnread() ? 'admin-table-row-unread' : '' }}">
                                        <td>
                                            <strong>{{ $c->name }}</strong>
                                            <span class="text-muted d-block small">{{ $c->email }}</span>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($c->subject, 48) }}</td>
                                        <td>{{ $c->created_at->format('M j, Y') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.contacts.show', $c) }}" class="btn btn-sm head-btn2">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No contact messages yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row jb-stack-row">
            <div class="col-lg-12">
                <div class="admin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="admin-card-title mb-0">Recent feedback</h2>
                        <a href="{{ route('admin.feedback.index') }}" class="admin-link">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>Summary</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentFeedback as $f)
                                    <tr class="{{ $f->isUnread() ? 'admin-table-row-unread' : '' }}">
                                        <td class="text-nowrap">
                                            <span class="badge badge-light">{{ ucfirst($f->type) }}</span>
                                            @if(!is_null($f->rating))
                                                <span class="text-muted small ml-2">★ {{ $f->rating }}/5</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $f->name ?: 'Anonymous' }}</strong>
                                            <span class="text-muted d-block small">{{ $f->email ?: '—' }}</span>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($f->subject ?: $f->message, 56) }}</td>
                                        <td>{{ $f->created_at->format('M j, Y') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.feedback.show', $f) }}" class="btn btn-sm head-btn2">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No feedback yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row jb-stack-row jb-stack-row--loose">
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="{{ route('admin.companies.index') }}" class="btn head-btn2 btn-block w-100">Manage companies</a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="{{ route('admin.jobs.index') }}" class="btn head-btn1 btn-block w-100">Manage all jobs</a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="{{ route('admin.contacts.index') }}" class="btn head-btn2 btn-block w-100">Contact messages</a>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <a href="{{ route('admin.feedback.index') }}" class="btn head-btn1 btn-block w-100">Feedback</a>
            </div>
        </div>
    </div>
</main>
@endsection
