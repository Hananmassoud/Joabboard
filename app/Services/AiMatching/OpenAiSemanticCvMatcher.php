<?php

namespace App\Services\AiMatching;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class OpenAiSemanticCvMatcher
{
    public function __construct(private readonly Client $http = new Client())
    {
    }

    public function match(array $input): array
    {
        $apiKey = (string) Config::get('services.groq.api_key');
        if ($apiKey === '') {
            throw new \RuntimeException('GROQ_API_KEY is not configured.');
        }

        $model = (string) Config::get('services.groq.model', 'llama-3.3-70b-versatile');
        $baseUrl = rtrim((string) Config::get('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $timeout = (int) Config::get('services.groq.timeout', 45);

        $jobDescription = trim((string) ($input['job_description'] ?? ''));
        $cvText = trim((string) ($input['cv_text'] ?? ''));

        if ($jobDescription === '' || $cvText === '') {
            throw new \InvalidArgumentException('job_description and cv_text are required.');
        }

        if (mb_strlen($jobDescription) > 12000) {
            $jobDescription = mb_substr($jobDescription, 0, 12000);
        }
        if (mb_strlen($cvText) > 14000) {
            $cvText = mb_substr($cvText, 0, 14000);
        }

        $messages = [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are an intelligent recruiter and ATS that evaluates candidates by semantic relevance (meaning), not keywords.',
                    'You MUST follow these strict rules:',
                    '1) Identify the job field (e.g., WordPress Developer, Web Developer, Data Analyst) from the job requirements.',
                    '2) Only count experience, skills, and projects directly related to the job field.',
                    '   - If the job is WordPress Developer: WordPress experience counts fully; general web dev is only partially relevant; unrelated experience is ignored.',
                    '3) Experience validation:',
                    '   - Extract total years of experience from the CV.',
                    '   - Compute relevant_experience_years for the job field ONLY (e.g., 3 years web + 1 year WP => relevant=1 for WP).',
                    '4) Education matching:',
                    '   - Match degree level (Bachelor/Master/etc).',
                    '   - Match field of study relevance to the job.',
                    '5) Skills matching (semantic):',
                    '   - Do NOT rely on exact keyword matches only.',
                    '   - Give partial credit for related skills (WordPress≈PHP/CMS; JavaScript≈React/Node).',
                    '6) Project relevance: count only projects related to the job field.',
                    '7) Strict rules:',
                    '   - Ignore unrelated fields completely.',
                    '   - Penalize missing critical requirements (e.g., required WordPress experience/skills).',
                    '8) Scoring weights:',
                    '   - Skills 40%, Experience 30%, Education 20%, Projects 10%.',
                    '9) Output ONE JSON object only (no markdown, no extra keys).',
                    '   Keys exactly: job_field, relevant_experience_years, skills_match, experience_match, education_match, projects_match, overall_match, reasoning.',
                    'All scores are 0-100. relevant_experience_years is a number (can be decimal).',
                ]),
            ],
            [
                'role' => 'user',
                'content' => "Job Requirements:\n{$jobDescription}\n\nCandidate CV:\n{$cvText}",
            ],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
            'max_tokens' => 700,
        ];

        $res = $this->http->post($baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => $timeout,
            'json' => $payload,
        ]);

        $data = json_decode((string) $res->getBody(), true);
        if (! is_array($data)) {
            throw new \RuntimeException('Groq API response was not valid JSON.');
        }

        $content = Arr::get($data, 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Groq API did not return message content.');
        }

        // The model should return JSON. We also handle small formatting mistakes (like ```json fences).
        $decoded = $this->parseJsonObject($content);

        $skills = $this->clampScore($decoded['skills_match'] ?? 0);
        $exp = $this->clampScore($decoded['experience_match'] ?? 0);
        $edu = $this->clampScore($decoded['education_match'] ?? 0);
        $proj = $this->clampScore($decoded['projects_match'] ?? 0);

        $overall = $this->weightedOverall($skills, $exp, $edu, $proj);

        return [
            'job_field' => trim((string) ($decoded['job_field'] ?? '')),
            'relevant_experience_years' => max(0.0, (float) ($decoded['relevant_experience_years'] ?? 0.0)),
            'skills_match' => $skills,
            'experience_match' => $exp,
            'education_match' => $edu,
            'projects_match' => $proj,
            'overall_match' => $overall,
            'reasoning' => trim((string) ($decoded['reasoning'] ?? '')),
        ];
    }

    private function parseJsonObject(string $raw): array
    {
        // Parse JSON safely (remove markdown fences, extract the first JSON object if needed).
        $raw = trim($raw);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $raw, $m)) {
            $raw = trim($m[1]);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            if (preg_match('/\{[\s\S]*\}/', $raw, $jm)) {
                $decoded = json_decode($jm[0], true);
            }
        }
        if (! is_array($decoded)) {
            throw new \RuntimeException('Groq message content was not valid JSON.');
        }

        return $decoded;
    }

    private function weightedOverall(int $skills, int $experience, int $education, int $projects): int
    {
        $wSkills = (float) Config::get('services.matching.weight_skills', 0.4);
        $wExp = (float) Config::get('services.matching.weight_experience', 0.3);
        $wEdu = (float) Config::get('services.matching.weight_education', 0.2);
        $wProjects = (float) Config::get('services.matching.weight_projects', 0.1);

        $v = ($skills * $wSkills)
            + ($experience * $wExp)
            + ($education * $wEdu)
            + ($projects * $wProjects);

        return $this->clampScore((int) round($v));
    }

    private function clampScore(mixed $value): int
    {
        $n = is_numeric($value) ? (int) round((float) $value) : 0;
        if ($n < 0) {
            return 0;
        }
        if ($n > 100) {
            return 100;
        }

        return $n;
    }
}

