<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobFavouriteController;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // Home page shows latest jobs posted by companies.
    $jobs = Job::with('company')->latest()->take(6)->get();

    // If logged in as applicant, load saved job ids so UI can highlight them.
    $favouriteJobIds = [];
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    if ($user instanceof User && $user->isApplicant()) {
        $favouriteJobIds = $user->favouriteJobs()->get()->pluck('id')->all();
    }

    return view('index', compact('jobs', 'favouriteJobIds'));
})->name('home');

Route::view('/about', 'about')->name('about');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/feedback', [FeedbackController::class, 'show'])->name('feedback');
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:feedback')
    ->name('feedback.store');

// Public job listing & details for applicants
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::get('/register/company', [AuthController::class, 'showCompanyRegisterForm'])->name('register.company');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user instanceof User && $user->isCompany()) {
            return redirect()->route('company.jobs.index');
        }

        // Applicants should see jobs posted by registered companies.
        $jobs = null;
        $appliedJobsCount = 0;
        $recentApplications = collect();
        $availableJobsCount = 0;
        $pendingApplicationsCount = 0;
        if ($user instanceof User && $user->isApplicant()) {
            // Dashboard lists only jobs the applicant saved (favourites) from the job board / home.
            $jobs = $user->favouriteJobs()
                ->with('company')
                ->orderByPivot('created_at', 'desc')
                ->take(8)
                ->get();
            $availableJobsCount = $user->favouriteJobs()->count();
            $appliedJobsCount = Application::where('user_id', $user->id)->count();
            $pendingApplicationsCount = Application::where('user_id', $user->id)->where('status', 'pending')->count();
            $recentApplications = Application::with('job.company')
                ->where('user_id', $user->id)
                ->latest()
                ->take(6)
                ->get();
        }

        return view('dashboard', compact('jobs', 'appliedJobsCount', 'recentApplications', 'availableJobsCount', 'pendingApplicationsCount'));
    })->name('dashboard');

    // Applicant: apply for a job
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])
        ->name('jobs.apply');

    Route::post('/jobs/{job}/favourite', [JobFavouriteController::class, 'toggle'])
        ->name('jobs.favourite.toggle');

    // Company panel
    Route::prefix('company')->name('company.')->group(function () {
        Route::get('/jobs', [CompanyJobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [CompanyJobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [CompanyJobController::class, 'store'])->name('jobs.store');
        Route::delete('/jobs/{job}', [CompanyJobController::class, 'destroy'])->name('jobs.destroy');
        Route::get('/jobs/{job}/applications', [CompanyJobController::class, 'applications'])->name('jobs.applications');
        Route::get('/jobs/{job}/applications/{application}/ai', [CompanyJobController::class, 'applicationAiStatus'])->name('jobs.applications.ai');
        Route::post('/jobs/{job}/applications/{application}/ai/rerun', [CompanyJobController::class, 'rerunApplicationAi'])->name('jobs.applications.ai.rerun');
        Route::post('/jobs/{job}/applications/{application}/select', [CompanyJobController::class, 'selectApplicant'])->name('jobs.applications.select');
        Route::post('/jobs/{job}/applications/{application}/read', [CompanyJobController::class, 'markApplicationRead'])->name('jobs.applications.read');
        Route::post('/jobs/{job}/applications/{application}/unread', [CompanyJobController::class, 'markApplicationUnread'])->name('jobs.applications.unread');
        Route::post('/notifications/read', [CompanyJobController::class, 'markNotificationsRead'])->name('notifications.read');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/companies', [AdminController::class, 'companies'])->name('companies.index');
        Route::delete('/companies/{user}', [AdminController::class, 'destroyCompany'])->name('companies.destroy');
        Route::get('/jobs', [AdminController::class, 'jobs'])->name('jobs.index');
        Route::delete('/jobs/{job}', [AdminController::class, 'destroyJob'])->name('jobs.destroy');
        Route::get('/contacts', [AdminController::class, 'contacts'])->name('contacts.index');
        Route::get('/contacts/{contact}', [AdminController::class, 'showContact'])->name('contacts.show');
        Route::delete('/contacts/{contact}', [AdminController::class, 'destroyContact'])->name('contacts.destroy');

        Route::get('/feedback', [AdminController::class, 'feedback'])->name('feedback.index');
        Route::get('/feedback/{feedback}', [AdminController::class, 'showFeedback'])->name('feedback.show');
        Route::delete('/feedback/{feedback}', [AdminController::class, 'destroyFeedback'])->name('feedback.destroy');
    });
});

