<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        // Combined registration page (applicant + company mode switcher).
        return view('auth.register');
    }

    public function showCompanyRegisterForm()
    {
        // Company-only registration page.
        // This shows all company fields at once to avoid "missing field" errors.
        return view('auth.register_company');
    }

    public function register(Request $request)
    {
        // We validate applicant and company registration in one endpoint.
        // For company accounts, extra fields are required (logo, background, etc).
        $maxYear = now()->year;
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                Rule::notIn([strtolower(config('admin.email'))]),
            ],
            'password'     => ['required', 'confirmed', 'min:8'],
            'role'         => ['required', 'in:company,applicant'],
            'company_name' => ['bail', 'exclude_unless:role,company', 'required', 'string', 'max:255'],
            'company_logo' => [
                'bail',
                'exclude_unless:role,company',
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'company_established_year' => [
                'bail',
                'exclude_unless:role,company',
                'required',
                'integer',
                'min:1900',
                'max:' . $maxYear,
            ],
            'company_description' => [
                'bail',
                'exclude_unless:role,company',
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'company_logo.required' => 'Company logo is required for company registration.',
            'company_name.required' => 'Company name is required for company registration.',
            'company_established_year.required' => 'Established year is required for company registration.',
            'company_description.required' => 'Company background is required for company registration.',
            'email.not_in' => 'This email is reserved for the administrator account.',
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));
        $normalizedName = trim($validated['name']);

        // Save company logo on the "public" disk so it can be displayed on the site.
        $companyLogoPath = null;
        if ($request->hasFile('company_logo')) {
            $companyLogoPath = $request->file('company_logo')->store('company-logos', 'public');
        }

        // Create the new user record.
        $user = User::create([
            'name'         => $normalizedName,
            'email'        => $normalizedEmail,
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'],
            'company_name' => $validated['role'] === 'company' ? trim((string) $validated['company_name']) : null,
            'company_logo' => $validated['role'] === 'company' ? $companyLogoPath : null,
            'company_established_year' => $validated['role'] === 'company'
                ? ($validated['company_established_year'] ?? null)
                : null,
            'company_description' => $validated['role'] === 'company'
                ? trim((string) ($validated['company_description'] ?? ''))
                : null,
        ]);

        // Login immediately after successful registration.
        Auth::login($user);

        // Redirect user based on role.
        return redirect()->route($user->isCompany() ? 'company.jobs.index' : 'dashboard');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Validate and attempt login with email + password.
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required'],
        ]);

        // Normalize email to avoid case issues (A@B.com vs a@b.com).
        $credentials['email'] = strtolower(trim($credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->withInput();
        }

        // Security: regenerate session after login.
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Default redirect after login.
        $default = $user->isCompany()
            ? route('company.jobs.index')
            : route('dashboard');

        return redirect()->intended($default);
    }

    public function logout(Request $request)
    {
        // Logout and invalidate the session.
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

