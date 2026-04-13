{{-- Register page: applicant or company. Posts to AuthController@register with multipart for company logo. --}}
@extends('layout.main')

@section('title', 'Register')

@section('content')
    <main class="section-pad-t30 auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8 col-md-10">
                    <div class="card p-4 p-md-5 mt-3 auth-card">
                        <div class="text-center mb-4">
                            <h2 class="mb-2">Create your account</h2>
                            <p class="text-muted mb-0">Choose your account type and complete the details below.</p>
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

                        {{-- enctype needed so file upload (company logo) works --}}
                        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="auth-form" novalidate>
                            @csrf

                            {{-- Shared fields for both applicant and company --}}
                            <div class="form-group mb-3">
                                <label for="name">Full name</label>
                                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- role=applicant | company — JS toggles extra company fields below --}}
                            <div class="form-group mb-3">
                                <label for="role">Account type</label>
                                <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required onchange="toggleRegistrationFields()">
                                    @php $role = old('role', 'applicant'); @endphp
                                    <option value="applicant" {{ $role === 'applicant' ? 'selected' : '' }}>Applicant</option>
                                    <option value="company" {{ $role === 'company' ? 'selected' : '' }}>Company</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="registration-mode-title" class="mb-3 auth-mode-title"></div>

                            {{-- Company-only block: hidden for applicants; required only when Company selected (see script) --}}
                            <div id="company-fields-wrapper" class="auth-company-box" style="{{ $role === 'company' ? '' : 'display:none;' }}">
                                <div class="form-group mb-3">
                                    <label for="company_name">Company name</label>
                                    <input id="company_name" type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                           value="{{ old('company_name') }}">
                                    @error('company_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="company_logo">Company logo</label>
                                    <input id="company_logo" type="file" name="company_logo" class="form-control @error('company_logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="text-muted">Required for company account. Max size 2MB.</small>
                                    @error('company_logo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="company_established_year">Company established year</label>
                                    <input id="company_established_year" type="number" name="company_established_year" class="form-control @error('company_established_year') is-invalid @enderror"
                                           value="{{ old('company_established_year') }}" min="1900" max="{{ now()->year }}">
                                    @error('company_established_year')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="company_description">Background of the company</label>
                                    <textarea id="company_description" name="company_description" rows="3" class="form-control @error('company_description') is-invalid @enderror"
                                              placeholder="Tell applicants about your company...">{{ old('company_description') }}</textarea>
                                    @error('company_description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
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
                                    Register
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

    {{-- Show/hide company fields and set HTML5 required on file inputs when needed --}}
    <script>
        function toggleRegistrationFields() {
            var roleSelect = document.getElementById('role');
            var companyWrapper = document.getElementById('company-fields-wrapper');
            var companyLogoInput = document.getElementById('company_logo');
            var companyNameInput = document.getElementById('company_name');
            var establishedYearInput = document.getElementById('company_established_year');
            var companyDescriptionInput = document.getElementById('company_description');
            var modeTitle = document.getElementById('registration-mode-title');

            var isCompany = roleSelect && roleSelect.value === 'company';

            if (companyWrapper) {
                companyWrapper.style.display = isCompany ? '' : 'none';
            }

            if (modeTitle) {
                modeTitle.textContent = isCompany
                    ? 'Company Registration: fill business details to publish jobs.'
                    : 'Applicant Registration: fill personal details to apply for jobs.';
            }

            if (companyLogoInput) companyLogoInput.required = isCompany;
            if (companyNameInput) companyNameInput.required = isCompany;
            if (establishedYearInput) establishedYearInput.required = isCompany;
            if (companyDescriptionInput) companyDescriptionInput.required = isCompany;
        }

        window.toggleRegistrationFields = toggleRegistrationFields;
        document.addEventListener('DOMContentLoaded', toggleRegistrationFields);
    </script>
@endsection

