<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessApplicationAiMatch;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function store(Request $request, Job $job)
    {
        $user = Auth::user();

        if (! $user || ! $user->isApplicant()) {
            abort(403, 'Only applicants can apply for jobs.');
        }

        $data = $request->validate([
            'applicant_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'string', 'email', 'max:255'],
            'cover_letter' => ['nullable', 'string'],
            'cv_url'       => ['nullable', 'url'],
            'cv'           => ['required', 'file', 'mimetypes:application/pdf', 'max:5120'],
        ]);

        $cvPath = null;
        $cvFile = null;
        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('cvs', 'public');
            $cvFile = $request->file('cv')->getClientOriginalName();
        }

        $application = Application::create([
            'job_id'       => $job->id,
            'user_id'      => $user->id,
            'applicant_name' => trim($data['applicant_name']),
            'applicant_email' => strtolower(trim($data['applicant_email'])),
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_url'       => $data['cv_url'] ?? null,
            'cv_path'      => $cvPath,
            'cv_file'      => $cvFile,
            'status'       => 'pending',
            'ai_status'    => 'pending',
        ]);

        ProcessApplicationAiMatch::dispatch($application->id);

        return redirect()->route('jobs.show', $job)->with('status', 'Application submitted successfully.');
    }
}

