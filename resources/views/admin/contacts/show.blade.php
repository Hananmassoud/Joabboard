@extends('layout.main')

@section('title', 'Admin — Message')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Contact message</h1>
                <p class="text-muted mb-0">Received {{ $message->created_at->format('M j, Y \a\t g:i A') }}</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.contacts.index') }}" class="btn head-btn1">All messages</a>
            </div>
        </div>

        <div class="admin-card admin-contact-detail">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $message->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9"><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>

                <dt class="col-sm-3">Subject</dt>
                <dd class="col-sm-9">{{ $message->subject }}</dd>

                <dt class="col-sm-3">Message</dt>
                <dd class="col-sm-9">
                    <div class="admin-contact-body">{!! nl2br(e($message->message)) !!}</div>
                </dd>
            </dl>
        </div>

        <div class="mt-3 d-flex flex-wrap align-items-center">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn head-btn2 mr-2 mb-2">Reply by email</a>
            <form action="{{ route('admin.contacts.destroy', $message) }}" method="POST" class="d-inline mb-2" onsubmit="return confirm('Delete this message?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete message</button>
            </form>
        </div>
    </div>
</main>
@endsection
