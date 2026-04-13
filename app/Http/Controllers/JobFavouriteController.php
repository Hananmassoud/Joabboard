<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobFavouriteController extends Controller
{
    public function toggle(Request $request, Job $job)
    {
        $user = Auth::user();

        if (! $user || ! $user->isApplicant()) {
            abort(403, 'Only applicants can save jobs.');
        }

        $saved = false;
        if ($user->favouriteJobs()->where('jobs.id', $job->id)->exists()) {
            $user->favouriteJobs()->detach($job->id);
            $message = 'Removed from saved jobs.';
        } else {
            $user->favouriteJobs()->attach($job->id);
            $saved = true;
            $message = 'Job saved. View it on your dashboard.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'saved' => $saved,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
