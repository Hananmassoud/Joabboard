@extends('layout.main')

@section('title', 'Admin — Contact messages')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Contact messages</h1>
                <p class="text-muted mb-0">Messages sent from the public contact form.</p>
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
                            <th>From</th>
                            <th>Subject</th>
                            <th>Received</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $msg)
                            <tr class="{{ $msg->isUnread() ? 'admin-table-row-unread' : '' }}">
                                <td>
                                    @if($msg->isUnread())
                                        <span class="badge badge-primary admin-badge-new">New</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $msg->name }}</strong><br>
                                    <span class="text-muted small">{{ $msg->email }}</span>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($msg->subject, 60) }}</td>
                                <td>{{ $msg->created_at->format('M j, Y g:i A') }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.contacts.show', $msg) }}" class="btn btn-sm head-btn2">View</a>
                                    <form action="{{ route('admin.contacts.destroy', $msg) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this message?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">No messages yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $messages->links() }}
        </div>
    </div>
</main>
@endsection
