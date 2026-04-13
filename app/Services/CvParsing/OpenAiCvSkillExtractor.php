<?php

namespace App\Services\CvParsing;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class OpenAiCvSkillExtractor
{
    public function __construct(private readonly Client $http = new Client())
    {
    }

    /**
     * Extract a comprehensive skills list from CV text.
     *
     * @return array{
     *   skills: array<int, array{name:string, category:string, aliases:array<int,string>, evidence:string}>,
     *   normalized: array<int,string>
     * }
     */
    public function extract(string $cvText): array
    {
        // This service extracts a detailed skills list from a CV using an LLM.
        // We store the result in the database so we can reuse it (no repeated AI calls).
        $apiKey = (string) Config::get('services.groq.api_key');
        if ($apiKey === '') {
            throw new \RuntimeException('GROQ_API_KEY is not configured.');
        }

        $model = (string) Config::get('services.groq.model', 'llama-3.3-70b-versatile');
        $baseUrl = rtrim((string) Config::get('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $timeout = (int) Config::get('services.groq.timeout', 45);

        $cvText = trim($cvText);
        if ($cvText === '') {
            return ['skills' => [], 'normalized' => []];
        }

        // Keep enough context to catch skills in different sections (tools, projects, experience).
        // If CV is extremely long, we cut it to avoid huge API calls.
        if (mb_strlen($cvText) > 24000) {
            $cvText = mb_substr($cvText, 0, 24000);
        }

        $messages = [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are a senior technical recruiter and CV parser.',
                    'Goal: extract ALL relevant skills from the CV with semantic understanding (not only explicit keywords).',
                    '',
                    'Rules:',
                    '- Extract skills from: skills sections, work experience, projects, certifications, tools, responsibilities.',
                    '- Include: programming languages, frameworks, libraries, databases, cloud, DevOps, CMS, analytics, testing, data tools, design tools, OS, networking, security, project mgmt, soft skills, domains (e.g., fintech).',
                    '- Expand implied skills when strongly supported:',
                    '  - Example: "Elementor/website building" -> WordPress (CMS), UI building.',
                    '  - Example: "REST APIs" -> API design, HTTP, JSON.',
                    '- Do NOT invent skills without evidence. If implied, evidence must reference the CV phrase that supports it.',
                    '- Prefer canonical names (e.g., "JavaScript" not "JS"; "Amazon Web Services" or "AWS").',
                    '- Deduplicate. Use consistent casing.',
                    '',
                    'Categories (use one): language, framework, library, database, cloud, devops, cms, analytics, testing, tooling, security, design, product, soft-skill, domain, other',
                    '',
                    'Output ONE JSON object only (no markdown, no extra keys).',
                    'Keys exactly: skills.',
                    'skills is an array of objects with keys exactly: name, category, aliases, evidence.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => "CV TEXT:\n" . $cvText,
            ],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            // Groq chat completions support response_format json_object.
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.0,
            'max_tokens' => 1200,
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

        $skills = Arr::get($decoded, 'skills', []);
        if (! is_array($skills)) {
            $skills = [];
        }

        $normalized = $this->normalizeSkillList($skills);

        return [
            'skills' => $skills,
            'normalized' => $normalized,
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

    /**
     * @param array<int, array{name?:mixed}> $skills
     * @return array<int, string>
     */
    private function normalizeSkillList(array $skills): array
    {
        // Convert the extracted skills into a clean, unique list of names.
        $names = [];
        foreach ($skills as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $name = preg_replace('/\s+/', ' ', $name) ?? $name;
            $names[] = $name;
        }

        $names = array_values(array_unique($names));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }
}

