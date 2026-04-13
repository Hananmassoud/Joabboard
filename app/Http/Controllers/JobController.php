<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::with('company')->latest()->paginate(10);
        $favouriteJobIds = [];
        $user = Auth::user();
        if ($user && $user->isApplicant()) {
            $favouriteJobIds = $user->favouriteJobs()->get()->pluck('id')->all();
        }

        return view('jobs.index', compact('jobs', 'favouriteJobIds'));
    }

    public function show(Job $job)
    {
        $job->load('company');
        $isFavourited = false;
        $existingApplication = null;
        $user = Auth::user();
        if ($user && $user->isApplicant()) {
            $isFavourited = $user->favouriteJobs()->where('jobs.id', $job->id)->exists();
            $existingApplication = Application::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->first();
        }

        return view('jobs.show', compact('job', 'isFavourited', 'existingApplication'));
    }
}

