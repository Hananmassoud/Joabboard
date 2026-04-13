{{-- Step 1 of reset: email only. PasswordResetController sends reset link (if mail configured). --}}
@extends('layout.main')

@section('title', 'Forgot password')

@section('content')
    <main class="section-pad-t30">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <div class="card p-4 p-md-5 mt-5 auth-card">
                        <div class="text-center mb-4">
                            <h2 class="mb-2">Forgot password</h2>
                            <p class="text-muted mb-0">Enter your email and we’ll send you a link to choose a new password.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Laravel built-in password broker --}}
                        <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
                            @csrf

                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required autofocus autocomplete="email">
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn head-btn2 w-100">
                                    Send reset link
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
