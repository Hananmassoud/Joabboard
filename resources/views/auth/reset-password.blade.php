{{-- Step 2 of reset: token from email + new password. Opens from /reset-password/{token}. --}}
@extends('layout.main')

@section('title', 'Set new password')

@section('content')
    <main class="section-pad-t30">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <div class="card p-4 p-md-5 mt-5 auth-card">
                        <div class="text-center mb-4">
                            <h2 class="mb-2">Set a new password</h2>
                            <p class="text-muted mb-0">Choose a strong password you haven’t used elsewhere.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}" class="auth-form" novalidate>
                            @csrf

                            {{-- Token proves the user clicked the email link --}}
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $email) }}" required autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">New password</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">At least 8 characters.</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="password_confirmation">Confirm new password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                       class="form-control" required autocomplete="new-password">
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn head-btn2 w-100">
                                    Update password
                                </button>
                            </div>

                            <p class="text-center mb-0">
                                <a href="{{ route('login') }}">Back to login</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
