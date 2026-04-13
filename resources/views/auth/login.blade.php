{{-- Login page: posts to AuthController@login. Session flash after password reset shows above the form. --}}
@extends('layout.main')

@section('title', 'Login')

@section('content')
    <main class="section-pad-t30 auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <div class="card p-4 p-md-5 mt-3 auth-card">
                        <div class="text-center mb-4">
                            <h2 class="mb-2">Welcome back</h2>
                            <p class="text-muted mb-0">Log in to continue to your dashboard.</p>
                        </div>

                        {{-- Success message e.g. after password reset email sent --}}
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

                        {{-- Standard Laravel login: email, password, optional remember --}}
                        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
                            @csrf

                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <label for="password" class="mb-0">Password</label>
                                    <a href="{{ route('password.request') }}" class="small auth-forgot-link">Forgot password?</a>
                                </div>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label class="d-flex align-items-center mb-0">
                                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                    Remember me
                                </label>
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn head-btn2 w-100">
                                    Login
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="{{ route('register') }}" class="btn head-btn1 w-100">Don’t have an account? Register</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

