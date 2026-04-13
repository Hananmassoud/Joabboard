@extends('layout.main')

@section('title', 'Admin — Feedback')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Feedback</h1>
                <p class="text-muted mb-0">Received {{ $item->created_at->format('M j, Y \a\t g:i A') }}</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.feedback.index') }}" class="btn head-btn1">All feedback</a>
            </div>
        </div>

        <div class="admin-card admin-contact-detail">
            <dl class="row mb-0">
                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9">{{ ucfirst($item->type) }}</dd>

                <dt class="col-sm-3">Rating</dt>
                <dd class="col-sm-9">{{ is_null($item->rating) ? '—' : ($item->rating.'/5') }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $item->name ?: 'Anonymous' }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">
                    @if($item->email)
                        <a href="mailto:{{ $item->email }}">{{ $item->email }}</a>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3">Subject</dt>
                <dd class="col-sm-9">{{ $item->subject ?: '—' }}</dd>

                <dt class="col-sm-3">Page URL</dt>
                <dd class="col-sm-9">
                    @if($item->page_url)
                        <a href="{{ $item->page_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($item->page_url, 90) }}</a>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3">Message</dt>
                <dd class="col-sm-9">
                    <div class="admin-contact-body">{!! nl2br(e($item->message)) !!}</div>
                </dd>
            </dl>
        </div>

        <div class="mt-3 d-flex flex-wrap align-items-center">
            @if($item->email)
                <a href="mailto:{{ $item->email }}?subject={{ rawurlencode('Re: '.($item->subject ?: 'Feedback')) }}" class="btn head-btn2 mr-2 mb-2">Reply by email</a>
            @endif
            <form action="{{ route('admin.feedback.destroy', $item) }}" method="POST" class="d-inline mb-2" onsubmit="return confirm('Delete this feedback?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete feedback</button>
            </form>
        </div>
    </div>
</main>
@endsection

