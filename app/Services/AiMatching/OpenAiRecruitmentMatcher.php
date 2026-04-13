<?php

namespace App\Services\AiMatching;

use App\Models\Job;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class OpenAiRecruitmentMatcher
{
    public function __construct(private readonly Client $http = new Client())
    {
    }

    /**
     * @return array{skills_match:int,experience_match:int,education_match:int,projects_match:int,overall_match:int,summary:?string}
     */
    public function score(Job $job, string $cvText): array
    {
        $apiKey = (string) Config::get('services.groq.api_key');
        if ($apiKey === '') {
            throw new \RuntimeException('GROQ_API_KEY is not configured.');
        }

        $model = (string) Config::get('services.groq.model', 'llama-3.3-70b-versatile');
        $baseUrl = rtrim((string) Config::get('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $timeout = (int) Config::get('services.groq.timeout', 45);

        $skills = $job->required_skills ?: [];
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', preg_split('/,|\n/', $skills) ?: []));
        }
        $skills = array_values($skills);
        if ($skills === []) {
            $skills = $this->deriveSkillsFromJobText($job->title . "\n" . $job->description);
        }

        // Keep payload bounded: CVs can be very long.
        $cvText = trim($cvText);
        if (mb_strlen($cvText) > 12000) {
            $cvText = mb_substr($cvText, 0, 12000);
        }

        $jobRequirements = [
            'title' => $job->title,
            'required_skills' => $skills,
            'min_experience_years' => $job->min_experience_years,
            'education_level' => $job->education_level,
            'experience_field' => $job->experience_field,
            'education_field' => $job->education_field,
            'description' => $job->description,
        ];

        // Deterministic hint so the model sees concrete differences between CVs (reduces "same score for everyone").
        $skillHint = $this->buildSkillOverlapHint($skills, $cvText);

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a strict recruitment ATS. You must score THIS candidate only, using evidence in the CV text. '
                    . 'Different CVs with different qualifications MUST receive different scores. '
                    . 'Never default every candidate to the same round number (e.g. 75). '
                    . 'Return one JSON object only, no markdown.',
            ],
            [
                'role' => 'user',
                'content' => "Score this single candidate against the job. Use the full 0-100 range; justify differences with evidence from the CV.\n\n"
                    . "Return JSON with keys exactly: skills_match, experience_match, education_match, projects_match, summary (string, max 3 sentences).\n"
                    . "Do NOT include overall_match; it will be computed server-side from your four scores.\n\n"
                    . "Rules:\n"
                    . "- skills_match: how well required skills (and close equivalents) are supported by the CV.\n"
                    . "- experience_match: vs min_experience_years and role relevance.\n"
                    . "  If the candidate's work experience field is not related to the job field, set experience_match = 0.\n"
                    . "- education_match: vs education_level AND whether the candidate's education field is related to the job field.\n"
                    . "  If the job is in computing/IT/software and the candidate's education is not in a computing-related field, set education_match = 0.\n"
                    . "- projects_match: relevance of the candidate's projects/portfolio to the job field and required skills (if projects are not mentioned, score low).\n"
                    . "- If the CV is weak on a dimension, score that dimension low even if other dimensions are strong.\n\n"
                    . "Reference signal (not the only factor; still read the CV): " . $skillHint . "\n\n"
                    . "Job requirements (JSON):\n" . json_encode($jobRequirements, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n"
                    . "---\nCandidate CV text:\n" . $cvText . "\n---",
            ],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
            // Slightly higher temperature helps avoid identical scores across different CVs when the model is over-cautious.
            'temperature' => 0.45,
            'max_tokens' => 500,
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

        $result = $this->parseScoreJson($content);

        $skillsMatch = $this->clampScore($result['skills_match'] ?? null);
        $expMatch = $this->clampScore($result['experience_match'] ?? null);
        $eduMatch = $this->clampScore($result['education_match'] ?? null);
        $projMatch = $this->clampScore($result['projects_match'] ?? null);

        if (! $this->isExperienceRelatedToJob($job, $skills, mb_strtolower($cvText))) {
            $expMatch = 0;
        }

        if (! $this->isEducationFieldRelatedToJob($job, $skills, mb_strtolower($cvText))) {
            $eduMatch = 0;
        }

        // Single source of truth for overall: weighted blend (matches product spec; avoids model copying one number).
        $overall = $this->weightedOverall($skillsMatch, $expMatch, $eduMatch, $projMatch);

        return [
            'skills_match' => $skillsMatch,
            'experience_match' => $expMatch,
            'education_match' => $eduMatch,
            'projects_match' => $projMatch,
            'overall_match' => $overall,
            'summary' => isset($result['summary']) && is_string($result['summary']) ? trim($result['summary']) : null,
        ];
    }

    private function isEducationFieldRelatedToJob(Job $job, array $jobSkills, string $cvLower): bool
    {
        $jobText = trim(implode("\n", array_filter([
            (string) ($job->experience_field ?? ''),
            (string) ($job->education_field ?? ''),
            (string) $job->title,
            implode(' ', $jobSkills),
            (string) $job->description,
        ])));

        $jobDomain = $this->detectDomain(mb_strtolower($jobText));
        $cvEduDomain = $this->detectEducationDomain($cvLower);

        if ($jobDomain === null || $cvEduDomain === null) {
            return true;
        }

        if ($jobDomain === 'computing') {
            return $cvEduDomain === 'computing';
        }

        return $jobDomain === $cvEduDomain;
    }

    private function detectEducationDomain(string $cvLower): ?string
    {
        $haystack = $cvLower;
        if (preg_match('/(education|qualification|degree)[\s\S]{0,800}/i', $cvLower, $m)) {
            $haystack = mb_strtolower($m[0]);
        }

        return $this->detectDomain($haystack);
    }

    private function isExperienceRelatedToJob(Job $job, array $jobSkills, string $cvLower): bool
    {
        $jobText = trim(implode("\n", array_filter([
            (string) ($job->experience_field ?? ''),
            (string) ($job->education_field ?? ''),
            (string) $job->title,
            implode(' ', $jobSkills),
            (string) $job->description,
        ])));

        $jobDomain = $this->detectDomain(mb_strtolower($jobText));
        $cvExpDomains = $this->detectExperienceDomains($cvLower);

        if ($jobDomain === null || $cvExpDomains === []) {
            return true;
        }

        if ($jobDomain === 'computing') {
            return in_array('computing', $cvExpDomains, true);
        }

        return in_array($jobDomain, $cvExpDomains, true);
    }

    /**
     * @return array<int, 'computing'|'business'|'finance'|'marketing'|'health'|'engineering'|'law'|'education'>
     */
    private function detectExperienceDomains(string $cvLower): array
    {
        $haystack = $cvLower;
        if (preg_match('/(work experience|experience|employment|professional experience)[\s\S]{0,1200}/i', $cvLower, $m)) {
            $haystack = mb_strtolower($m[0]);
        }

        return $this->detectDomains($haystack);
    }

    /**
     * Detect multiple domains present in the text (for multi-field experience/CVs).
     *
     * @return array<int, 'computing'|'business'|'finance'|'marketing'|'health'|'engineering'|'law'|'education'>
     */
    private function detectDomains(string $textLower): array
    {
        $domains = [];
        foreach (['computing', 'finance', 'marketing', 'health', 'engineering', 'law', 'education', 'business'] as $d) {
            if ($this->detectDomainFor($d, $textLower)) {
                $domains[] = $d;
            }
        }

        return array_values(array_unique($domains));
    }

    private function detectDomainFor(string $domain, string $textLower): bool
    {
        $t = $textLower;
        $has = function (array $needles) use ($t): bool {
            foreach ($needles as $n) {
                if ($n !== '' && mb_strpos($t, $n) !== false) {
                    return true;
                }
            }
            return false;
        };

        return match ($domain) {
            'computing' => $has(['computer', 'comput', 'software', 'it ', ' it', 'information technology', 'data science', 'machine learning', 'ai ', 'ml ', 'cyber', 'network', 'programming', 'developer', 'web development']),
            'finance' => $has(['account', 'accounting', 'finance', 'financial', 'cfa', 'audit', 'auditing', 'tax']),
            'marketing' => $has(['marketing', 'seo', 'content', 'digital marketing', 'brand', 'branding', 'social media']),
            'health' => $has(['nursing', 'medical', 'medicine', 'pharmacy', 'clinical', 'healthcare', 'hospital', 'dent', 'patient']),
            'engineering' => $has(['civil engineering', 'mechanical', 'electrical', 'electronics', 'mechatronics', 'chemical engineering', 'industrial engineering', 'architect', 'architecture']),
            'law' => $has(['law', 'llb', 'll.m', 'legal', 'attorney', 'advocate', 'litigation']),
            'education' => $has(['education', 'teaching', 'teacher', 'b.ed', 'm.ed', 'pedagogy']),
            'business' => $has(['business', 'management', 'mba', 'bba', 'administration', 'commerce', 'entrepreneur', 'hr ', 'human resource']),
            default => false,
        };
    }

    /**
     * @return 'computing'|'business'|'finance'|'marketing'|'health'|'engineering'|'law'|'education'|null
     */
    private function detectDomain(string $textLower): ?string
    {
        $t = $textLower;

        $has = function (array $needles) use ($t): bool {
            foreach ($needles as $n) {
                if ($n !== '' && mb_strpos($t, $n) !== false) {
                    return true;
                }
            }
            return false;
        };

        if ($has(['computer', 'comput', 'software', 'it ', ' it', 'information technology', 'data science', 'machine learning', 'ai ', 'ml ', 'cyber', 'network', 'programming', 'developer', 'engineering (software)', 'web development'])) {
            return 'computing';
        }
        if ($has(['account', 'accounting', 'finance', 'financial', 'cfa', 'audit', 'auditing', 'tax'])) {
            return 'finance';
        }
        if ($has(['marketing', 'seo', 'content', 'digital marketing', 'brand', 'branding', 'social media'])) {
            return 'marketing';
        }
        if ($has(['nursing', 'medical', 'medicine', 'pharmacy', 'clinical', 'healthcare', 'hospital', 'dent', 'patient'])) {
            return 'health';
        }
        if ($has(['civil engineering', 'mechanical', 'electrical', 'electronics', 'mechatronics', 'chemical engineering', 'industrial engineering', 'architect', 'architecture'])) {
            return 'engineering';
        }
        if ($has(['law', 'llb', 'll.m', 'legal', 'attorney', 'advocate', 'litigation'])) {
            return 'law';
        }
        if ($has(['education', 'teaching', 'teacher', 'b.ed', 'm.ed', 'pedagogy'])) {
            return 'education';
        }
        if ($has(['business', 'management', 'mba', 'bba', 'administration', 'commerce', 'entrepreneur', 'hr ', 'human resource'])) {
            return 'business';
        }

        return null;
    }

    /**
     * Strip markdown fences, tolerate extra prose, normalize key casing.
     */
    private function parseScoreJson(string $raw): array
    {
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

        return $this->normalizeScoreKeys($decoded);
    }

    private function normalizeScoreKeys(array $row): array
    {
        $flat = [];
        foreach ($row as $k => $v) {
            $key = strtolower((string) $k);
            $key = str_replace([' ', '-'], '_', $key);
            $flat[$key] = $v;
        }

        $aliases = [
            'skills' => 'skills_match',
            'skill_match' => 'skills_match',
            'skillsmatch' => 'skills_match',
            'experience' => 'experience_match',
            'experiencematch' => 'experience_match',
            'education' => 'education_match',
            'educationmatch' => 'education_match',
            'projects' => 'projects_match',
            'project' => 'projects_match',
            'projectsmatch' => 'projects_match',
            'overall' => 'overall_match',
        ];
        foreach ($aliases as $from => $to) {
            if (! array_key_exists($to, $flat) && array_key_exists($from, $flat)) {
                $flat[$to] = $flat[$from];
            }
        }

        return $flat;
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

    private function buildSkillOverlapHint(array $requiredSkills, string $cvLower): string
    {
        $haystack = mb_strtolower($cvLower);
        $hits = 0;
        foreach ($requiredSkills as $s) {
            $needle = mb_strtolower(trim((string) $s));
            if ($needle === '') {
                continue;
            }
            if (mb_strpos($haystack, $needle) !== false) {
                $hits++;
            }
        }

        $total = count($requiredSkills);

        return "Required skills listed: {$total}. Literal mentions found in CV: {$hits} of {$total}.";
    }

    /**
     * Lightweight keyword extraction for jobs missing explicit required_skills.
     * This is a heuristic (not perfect) but produces per-job signals and avoids generic scoring.
     *
     * @return string[]
     */
    private function deriveSkillsFromJobText(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9\+\#\.\s]/u', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/', $text) ?: [];

        $stop = array_flip([
            'the','and','for','with','you','your','will','this','that','from','are','our','job','role','work','must','have',
            'to','of','in','on','at','a','an','as','is','be','by','or','we','they','it','their','them','can','may','not',
            'required','requirement','requirements','experience','years','year','skills','skill','education','level',
        ]);

        $freq = [];
        foreach ($tokens as $t) {
            $t = trim($t);
            if ($t === '' || strlen($t) < 3) {
                continue;
            }
            if (isset($stop[$t])) {
                continue;
            }
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }

        arsort($freq);
        $top = array_keys(array_slice($freq, 0, 15, true));

        return array_values($top);
    }

    private function clampScore(mixed $value): int
    {
        $n = is_numeric($value) ? (int) $value : 0;
        if ($n < 0) {
            return 0;
        }
        if ($n > 100) {
            return 100;
        }

        return $n;
    }
}

