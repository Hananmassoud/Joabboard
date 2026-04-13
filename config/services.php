<?php

return [

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

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout' => (int) env('GROQ_TIMEOUT', 45),
    ],
    'matching' => [
        'engine' => env('MATCH_ENGINE', 'deterministic'),
        'weight_skills' => (float) env('MATCH_WEIGHT_SKILLS', 0.40),
        'weight_experience' => (float) env('MATCH_WEIGHT_EXPERIENCE', 0.30),
        'weight_education' => (float) env('MATCH_WEIGHT_EDUCATION', 0.20),
        'weight_projects' => (float) env('MATCH_WEIGHT_PROJECTS', 0.10),
    ],

];
