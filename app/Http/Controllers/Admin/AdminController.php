<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ContactMessage;
use App\Models\Feedback;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'companies'    => User::where('role', 'company')->count(),
            'applicants'   => User::where('role', 'applicant')->count(),
            'jobs'         => Job::count(),
            'applications' => Application::count(),
            'contact_messages' => ContactMessage::count(),
            'contact_unread'   => ContactMessage::whereNull('read_at')->count(),
            'feedback'         => Feedback::count(),
            'feedback_unread'  => Feedback::whereNull('read_at')->count(),
        ];

        $recentJobs = Job::with('company')->latest()->take(6)->get();
        $recentContacts = ContactMessage::latest()->take(5)->get();
        $recentFeedback = Feedback::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentJobs', 'recentContacts', 'recentFeedback'));
    }

    public function companies()
    {
        $companies = User::where('role', 'company')
            ->withCount('jobs')
            ->latest()
            ->paginate(15);

        return view('admin.companies.index', compact('companies'));
    }

    public function destroyCompany(User $user)
    {
        if (! $user->isCompany()) {
            abort(404);
        }

        if ($user->company_logo) {
            Storage::disk('public')->delete($user->company_logo);
        }

        $user->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Company account and its jobs have been removed.');
    }

    public function jobs()
    {
        $jobs = Job::with('company')->latest()->paginate(15);

        return view('admin.jobs.index', compact('jobs'));
    }

    public function destroyJob(Job $job)
    {
        $job->delete();

        return redirect()
            ->route('admin.jobs.index')
            ->with('status', 'Job removed successfully.');
    }

    public function contacts()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return view('admin.contacts.index', compact('messages'));
    }

    public function showContact(ContactMessage $contact)
    {
        $contact->markAsRead();

        return view('admin.contacts.show', ['message' => $contact]);
    }

    public function destroyContact(ContactMessage $contact)
    {
        $contact->delete();

        return redirect()
            ->route('admin.contacts.index')
            ->with('status', 'Message removed.');
    }

    public function feedback()
    {
        $items = Feedback::latest()->paginate(20);

        return view('admin.feedback.index', compact('items'));
    }

    public function showFeedback(Feedback $feedback)
    {
        $feedback->markAsRead();

        return view('admin.feedback.show', ['item' => $feedback]);
    }

    public function destroyFeedback(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()
            ->route('admin.feedback.index')
            ->with('status', 'Feedback removed.');
    }
}
