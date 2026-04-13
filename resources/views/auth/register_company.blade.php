{{-- Company-only registration: all fields visible at once. Same POST as mixed register; role fixed via hidden input. --}}
@extends('layout.main')

@section('title', 'Company Register')

@section('content')
    <main class="section-pad-t30 auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8 col-md-10">
                    <div class="card p-4 p-md-5 mt-3 auth-card">
                        <div class="text-center mb-4">
                            <h2 class="mb-2">Create company account</h2>
                            <p class="text-muted mb-0">Fill all company details to start posting jobs.</p>
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

                        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="auth-form" novalidate>
                            @csrf
                            {{-- Tells AuthController to validate company fields (logo, background, etc.) --}}
                            <input type="hidden" name="role" value="company">

                            <div class="form-group mb-3">
                                <label for="name">Owner Name</label>
                                <input id="name" type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="email">Company Email</label>
                                <input id="email" type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_name">Company name</label>
                                <input id="company_name" type="text" name="company_name"
                                       class="form-control @error('company_name') is-invalid @enderror"
                                       value="{{ old('company_name') }}" required>
                                @error('company_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_logo">Upload company logo</label>
                                <input id="company_logo" type="file" name="company_logo"
                                       class="form-control @error('company_logo') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png,.webp" required>
                                <small class="text-muted">Max size 2MB.</small>
                                @error('company_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_established_year">Company established year</label>
                                <input id="company_established_year" type="number" name="company_established_year"
                                       class="form-control @error('company_established_year') is-invalid @enderror"
                                       value="{{ old('company_established_year') }}" min="1900" max="{{ now()->year }}" required>
                                @error('company_established_year')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_description">Background of the company</label>
                                <textarea id="company_description" name="company_description" rows="3"
                                          class="form-control @error('company_description') is-invalid @enderror"
                                          placeholder="Tell applicants about your company..." required>{{ old('company_description') }}</textarea>
                                @error('company_description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input id="password" type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror" required>
                                <small class="text-muted">Use at least 8 characters.</small>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label for="password_confirmation">Confirm Password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                       class="form-control" required>
                            </div>

                            <div class="text-center mb-3">
                                <button type="submit" class="btn head-btn2 w-100">
                                    Register Company
                                </button>
                            </div>

                            <p class="text-center mb-0">
                                Already have an account?
                                <a href="{{ route('login') }}">Login here</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

