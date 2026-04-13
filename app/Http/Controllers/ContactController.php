<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create([
            'name'    => trim($validated['name']),
            'email'   => strtolower(trim($validated['email'])),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
        ]);

        return redirect()
            ->route('contact')
            ->with('status', 'Thank you for your message. We will get back to you shortly.');
    }
}

