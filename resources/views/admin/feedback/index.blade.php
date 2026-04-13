@extends('layout.main')

@section('title', 'Admin — Feedback')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Feedback</h1>
                <p class="text-muted mb-0">Ideas, bug reports, and suggestions submitted by users.</p>
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
                            <th style="width: 1%;"></th>
                            <th>Type</th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Received</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="{{ $item->isUnread() ? 'admin-table-row-unread' : '' }}">
                                <td>
                                    @if($item->isUnread())
                                        <span class="badge badge-primary admin-badge-new">New</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge badge-light">{{ ucfirst($item->type) }}</span>
                                    @if(!is_null($item->rating))
                                        <span class="text-muted small ml-2">★ {{ $item->rating }}/5</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $item->name ?: 'Anonymous' }}</strong><br>
                                    <span class="text-muted small">{{ $item->email ?: '—' }}</span>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($item->subject ?: $item->message, 60) }}</td>
                                <td>{{ $item->created_at->format('M j, Y g:i A') }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.feedback.show', $item) }}" class="btn btn-sm head-btn2">View</a>
                                    <form action="{{ route('admin.feedback.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this feedback?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No feedback yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </div>
</main>
@endsection

