<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function show()
    {
        return view('feedback');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:general,bug,feature,content,other'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],

            // Optional contact details for follow-up
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],

            // Basic bot trap (must be empty)
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        Feedback::create([
            'user_id' => auth()->id(),
            'name' => isset($validated['name']) ? trim($validated['name']) : null,
            'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'type' => $validated['type'],
            'rating' => $validated['rating'] ?? null,
            'subject' => isset($validated['subject']) ? trim($validated['subject']) : null,
            'message' => trim($validated['message']),
            'page_url' => $request->headers->get('referer') ?: $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        return redirect()
            ->route('feedback')
            ->with('status', 'Thank you — your feedback has been submitted.');
    }
}

