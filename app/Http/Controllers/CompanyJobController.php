<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessApplicationAiMatch;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyJobController extends Controller
{
    protected function ensureCompany()
    {
        $user = Auth::user();

        if (! $user || ! $user->isCompany()) {
            abort(403, 'Only company users can access this area.');
        }

        return $user;
    }

    public function index()
    {
        $user = $this->ensureCompany();

        $jobs = Job::withCount('applications')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $lastSeen = $user->company_notifications_last_seen_at;

        $recentApplications = Application::with(['applicant', 'job'])
            ->whereHas('job', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->take(8)
            ->get();

        $unreadApplicationsCount = Application::whereHas('job', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->when($lastSeen, function ($query) use ($lastSeen) {
                $query->where('created_at', '>', $lastSeen);
            })
            ->count();

        $totalApplicationsCount = Application::whereHas('job', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        return view('company.jobs.index', compact('jobs', 'recentApplications', 'unreadApplicationsCount', 'totalApplicationsCount'));
    }

    public function markNotificationsRead()
    {
        $user = $this->ensureCompany();

        $user->forceFill([
            'company_notifications_last_seen_at' => now(),
        ])->save();

        return redirect()->route('company.jobs.index')->with('status', 'Notifications marked as read.');
    }

    public function create()
    {
        $this->ensureCompany();

        return view('company.jobs.create');
    }

    public function store(Request $request)
    {
        $user = $this->ensureCompany();

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            // Required for accurate AI matching
            'required_skills' => ['required', 'string', 'max:2000'],
            'min_experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'experience_field' => ['required', 'string', 'max:120'],
            'education_level' => ['nullable', 'string', 'max:80'],
            'education_field' => ['required', 'string', 'max:120'],
            'location'    => ['nullable', 'string', 'max:255'],
            'salary'      => ['nullable', 'string', 'max:255'],
            'job_type'    => ['nullable', 'string', 'max:255'],
        ]);

        $skills = null;
        $parts = preg_split('/,|\n/', (string) $data['required_skills']) ?: [];
        $skills = array_values(array_filter(array_map(fn ($s) => trim($s), $parts)));
        if (count($skills) < 3) {
            return back()
                ->withErrors(['required_skills' => 'Please add at least 3 required skills for better matching.'])
                ->withInput();
        }

        Job::create([
            'user_id'     => $user->id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'required_skills' => $skills,
            'min_experience_years' => $data['min_experience_years'] ?? null,
            'experience_field' => $data['experience_field'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'education_field' => $data['education_field'] ?? null,
            'location'    => $data['location'] ?? null,
            'salary'      => $data['salary'] ?? null,
            'job_type'    => $data['job_type'] ?? null,
        ]);

        return redirect()->route('company.jobs.index')->with('status', 'Job created successfully.');
    }

    public function destroy(Job $job)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id) {
            abort(403);
        }

        $job->delete();

        return redirect()->route('company.jobs.index')->with('status', 'Job deleted successfully.');
    }

    public function applications(Job $job)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id) {
            abort(403);
        }

        $minMatch = request()->integer('min_match');
        $readFilter = request()->query('read');
        if (! in_array($readFilter, ['unread', 'read'], true)) {
            $readFilter = null;
        }

        $applicationsQuery = Application::with('applicant')
            ->where('job_id', $job->id);

        if ($minMatch > 0) {
            $applicationsQuery->whereNotNull('overall_match')->where('overall_match', '>=', $minMatch);
        }

        if ($readFilter === 'unread') {
            $applicationsQuery->whereNull('company_read_at');
        } elseif ($readFilter === 'read') {
            $applicationsQuery->whereNotNull('company_read_at');
        }

        $applications = $applicationsQuery
            ->orderByDesc(DB::raw('(company_read_at IS NULL)'))
            ->orderByDesc('overall_match')
            ->orderByDesc('id')
            ->get();

        // Build URLs while preserving min_match + read query params unless overridden.
        $applicationsUrl = function (array $query = []) use ($job) {
            $params = array_merge([
                'job' => $job,
                'min_match' => request()->integer('min_match') ?: null,
                'read' => request()->query('read'),
            ], $query);
            if (isset($params['read']) && ! in_array($params['read'], ['unread', 'read'], true)) {
                $params['read'] = null;
            }

            return route('company.jobs.applications', array_filter(
                $params,
                fn ($v) => $v !== null && $v !== ''
            ));
        };

        return view('company.jobs.applications', compact('job', 'applications', 'minMatch', 'readFilter', 'applicationsUrl'));
    }

    public function markApplicationRead(Job $job, Application $application)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id || $application->job_id !== $job->id) {
            abort(404);
        }

        $application->markReadByCompany();

        return back()->with('status', 'Marked as reviewed.');
    }

    public function markApplicationUnread(Job $job, Application $application)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id || $application->job_id !== $job->id) {
            abort(404);
        }

        $application->markUnreadByCompany();

        return back()->with('status', 'Marked as not reviewed.');
    }

    public function applicationAiStatus(Job $job, Application $application)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id) {
            abort(403);
        }

        if ($application->job_id !== $job->id) {
            abort(404);
        }

        return response()->json([
            'ai_status' => $application->ai_status,
            'skills_match' => $application->skills_match,
            'experience_match' => $application->experience_match,
            'education_match' => $application->education_match,
            'projects_match' => $application->projects_match,
            'overall_match' => $application->overall_match,
            'ai_summary' => $application->ai_summary,
            'ai_error' => $application->ai_error,
            'ai_processed_at' => optional($application->ai_processed_at)->toIso8601String(),
            'weights' => [
                'skills' => (float) config('services.matching.weight_skills', 0.4),
                'experience' => (float) config('services.matching.weight_experience', 0.3),
                'education' => (float) config('services.matching.weight_education', 0.2),
                'projects' => (float) config('services.matching.weight_projects', 0.1),
            ],
        ]);
    }

    public function rerunApplicationAi(Job $job, Application $application)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id) {
            abort(403);
        }

        if ($application->job_id !== $job->id) {
            abort(404);
        }

        $application->forceFill([
            'ai_status' => 'pending',
            'skills_match' => null,
            'experience_match' => null,
            'education_match' => null,
            'overall_match' => null,
            'ai_summary' => null,
            'ai_error' => null,
            'ai_processed_at' => null,
        ])->save();

        ProcessApplicationAiMatch::dispatch($application->id);

        return redirect()
            ->route('company.jobs.applications', $job)
            ->with('status', 'AI matching has been queued again for this application.');
    }

    public function selectApplicant(Job $job, Application $application)
    {
        $user = $this->ensureCompany();

        if ($job->user_id !== $user->id) {
            abort(403);
        }

        if ($application->job_id !== $job->id) {
            abort(404);
        }

        DB::transaction(function () use ($job, $application) {
            Application::where('job_id', $job->id)
                ->where('id', '!=', $application->id)
                ->update(['status' => 'rejected']);

            $application->update(['status' => 'accepted']);
        });

        return redirect()
            ->route('company.jobs.applications', $job)
            ->with('status', 'Applicant selected successfully.');
    }
}

