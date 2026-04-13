<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Groq (OpenAI-compatible Chat Completions API only — no OpenAI.com calls).
    | https://console.groq.com — set GROQ_API_KEY and optionally GROQ_MODEL.
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout' => (int) env('GROQ_TIMEOUT', 45),
    ],

    /*
    | Deterministic match weights (must sum to 1.0). Used for overall_match only.
    | Default: 40% skills, 30% experience, 20% education, 10% projects.
    */
    'matching' => [
        // deterministic|openai|openai_semantic
        'engine' => env('MATCH_ENGINE', 'deterministic'),
        'weight_skills' => (float) env('MATCH_WEIGHT_SKILLS', 0.40),
        'weight_experience' => (float) env('MATCH_WEIGHT_EXPERIENCE', 0.30),
        'weight_education' => (float) env('MATCH_WEIGHT_EDUCATION', 0.20),
        'weight_projects' => (float) env('MATCH_WEIGHT_PROJECTS', 0.10),
    ],

];
