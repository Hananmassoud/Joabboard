<?php

namespace App\Services\AiMatching;

use App\Models\Job;
class DeterministicMatchScorer
{
    public function score(Job $job, string $cvText): array
    {
        $wSkills = (float) config('services.matching.weight_skills', 0.4);
        $wExp = (float) config('services.matching.weight_experience', 0.3);
        $wEdu = (float) config('services.matching.weight_education', 0.2);
        $wProjects = (float) config('services.matching.weight_projects', 0.1);

        $cv = mb_strtolower(trim($cvText));
        if (mb_strlen($cv) > 12000) {
            $cv = mb_substr($cv, 0, 12000);
        }

        $skills = $job->required_skills ?: [];
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', preg_split('/,|\n/', $skills) ?: []));
        }
        $skills = array_values($skills);
        if ($skills === []) {
            $skills = $this->deriveSkillsFromJobText($job->title . "\n" . $job->description);
        }

        $skillsDetail = $this->scoreSkillsDetail($skills, $cv);
        $experienceDetail = $this->scoreExperienceDetail($job, $skills, $cv);
        $educationDetail = $this->scoreEducationDetail($job, $skills, $cv);
        $projectsDetail = $this->scoreProjectsDetail($job, $skills, $cv);

        $skillsMatch = $skillsDetail['score'];
        $experienceMatch = $experienceDetail['score'];
        $educationMatch = $educationDetail['score'];
        $projectsMatch = $projectsDetail['score'];

        $overall = (int) round(
            ($skillsMatch * $wSkills)
            + ($experienceMatch * $wExp)
            + ($educationMatch * $wEdu)
            + ($projectsMatch * $wProjects)
        );
        $overall = max(0, min(100, $overall));

        $summary = $this->buildSummary($skillsDetail, $experienceDetail, $educationDetail, $projectsDetail, $overall, $job);

        return [
            'skills_match' => $skillsMatch,
            'experience_match' => $experienceMatch,
            'education_match' => $educationMatch,
            'projects_match' => $projectsMatch,
            'overall_match' => $overall,
            'summary' => $summary,
        ];
    }

    /**
     * @param  string[]  $requiredSkills
     * @return array{score:int,hits:int,total:int,required:list<string>,matched:list<string>,missing:list<string>}
     */
    private function scoreSkillsDetail(array $requiredSkills, string $cvLower): array
    {
        $requiredLabels = [];
        foreach ($requiredSkills as $s) {
            $label = trim((string) $s);
            if ($label !== '') {
                $requiredLabels[] = $label;
            }
        }
        $total = count($requiredLabels);
        if ($total === 0) {
            return ['score' => 0, 'hits' => 0, 'total' => 0, 'required' => [], 'matched' => [], 'missing' => []];
        }

        $hits = 0;
        $matched = [];
        $missing = [];
        foreach ($requiredLabels as $label) {
            $needle = mb_strtolower($label);
            if ($this->cvContainsSkill($cvLower, $needle)) {
                $hits++;
                $matched[] = $label;
            } else {
                $missing[] = $label;
            }
        }

        $score = (int) round(($hits / $total) * 100);

        return ['score' => $score, 'hits' => $hits, 'total' => $total, 'required' => $requiredLabels, 'matched' => $matched, 'missing' => $missing];
    }

    /**
     * @param  string[]  $jobSkills
     * @return array{score:int,years:?float,required_years:?int,related:bool,job_field:?string}
     */
    private function scoreExperienceDetail(Job $job, array $jobSkills, string $cvLower): array
    {
        $minYears = $job->min_experience_years;
        $jobField = $job->experience_field ? trim((string) $job->experience_field) : null;
        if ($minYears === null || $minYears <= 0) {
            return ['score' => 0, 'years' => null, 'required_years' => $minYears, 'related' => true, 'job_field' => $jobField];
        }

        $years = $this->extractYearsExperience($cvLower);
        if ($years === null) {
            return ['score' => 0, 'years' => null, 'required_years' => $minYears, 'related' => true, 'job_field' => $jobField];
        }

        $related = $this->isExperienceRelatedToJob($job, $jobSkills, $cvLower);
        if (! $related) {
            return ['score' => 0, 'years' => $years, 'required_years' => $minYears, 'related' => false, 'job_field' => $jobField];
        }

        if ($years >= $minYears) {
            return ['score' => 100, 'years' => $years, 'required_years' => $minYears, 'related' => true, 'job_field' => $jobField];
        }

        return [
            'score' => (int) round(100 * ($years / max(1, $minYears))),
            'years' => $years,
            'required_years' => $minYears,
            'related' => true,
            'job_field' => $jobField,
        ];
    }

    /**
     * @param  string[]  $jobSkills
     * @return array{score:int,required_level:?string,detected_rank:?int,required_rank:?int,related:bool,job_requirement_text:?string,applicant_label:?string}
     */
    private function scoreEducationDetail(Job $job, array $jobSkills, string $cvLower): array
    {
        $requiredLevel = $job->education_level;
        $jobReqText = $this->formatJobEducationRequirement($job);
        $applicantLabel = $this->detectApplicantEducationLabel($cvLower);

        $requiredRank = $this->educationRank($requiredLevel);
        if ($requiredRank === null) {
            return [
                'score' => 0,
                'required_level' => $requiredLevel,
                'detected_rank' => null,
                'required_rank' => null,
                'related' => true,
                'job_requirement_text' => $jobReqText,
                'applicant_label' => $applicantLabel,
            ];
        }

        $cvRank = $this->detectHighestEducationRank($cvLower);
        if ($cvRank === null) {
            return [
                'score' => 0,
                'required_level' => $requiredLevel,
                'detected_rank' => null,
                'required_rank' => $requiredRank,
                'related' => true,
                'job_requirement_text' => $jobReqText,
                'applicant_label' => $applicantLabel,
            ];
        }

        $related = $this->isEducationFieldRelatedToJob($job, $jobSkills, $cvLower);
        if (! $related) {
            return [
                'score' => 0,
                'required_level' => $requiredLevel,
                'detected_rank' => $cvRank,
                'required_rank' => $requiredRank,
                'related' => false,
                'job_requirement_text' => $jobReqText,
                'applicant_label' => $applicantLabel ?: $this->rankToEducationLabel($cvRank),
            ];
        }

        if ($cvRank >= $requiredRank) {
            return [
                'score' => 100,
                'required_level' => $requiredLevel,
                'detected_rank' => $cvRank,
                'required_rank' => $requiredRank,
                'related' => true,
                'job_requirement_text' => $jobReqText,
                'applicant_label' => $applicantLabel ?: $this->rankToEducationLabel($cvRank),
            ];
        }

        return [
            'score' => (int) round(100 * ($cvRank / max(1, $requiredRank))),
            'required_level' => $requiredLevel,
            'detected_rank' => $cvRank,
            'required_rank' => $requiredRank,
            'related' => true,
            'job_requirement_text' => $jobReqText,
            'applicant_label' => $applicantLabel ?: $this->rankToEducationLabel($cvRank),
        ];
    }

    private function formatJobEducationRequirement(Job $job): ?string
    {
        $level = $job->education_level ? trim((string) $job->education_level) : '';
        $field = $job->education_field ? trim((string) $job->education_field) : '';

        if ($level === '' && $field === '') {
            return null;
        }

        if ($level !== '' && $field !== '') {
            // Avoid duplication if field text already includes the level wording
            $fl = mb_strtolower($field);
            if (str_contains($fl, mb_strtolower($level))) {
                return $field;
            }

            return $level.' — '.$field;
        }

        return $level !== '' ? $level : $field;
    }

    private function rankToEducationLabel(?int $rank): ?string
    {
        return match ($rank) {
            6 => 'PhD / Doctorate',
            5 => 'Master',
            4 => 'Bachelor',
            3 => 'Diploma / Associate',
            2 => 'High school',
            default => null,
        };
    }

    /**
     * Short phrase inferred from CV education section (best-effort).
     */
    private function detectApplicantEducationLabel(string $cvLower): ?string
    {
        $section = $cvLower;
        if (preg_match('/(education|qualifications?|academic)[\s\S]{0,1200}/i', $cvLower, $m)) {
            $section = mb_strtolower($m[0]);
        }

        $degree = null;
        foreach (['ph.d', 'phd', 'doctorate', 'doctor of'] as $n) {
            if (mb_strpos($section, $n) !== false) {
                $degree = 'PhD';
                break;
            }
        }
        if ($degree === null) {
            foreach (['master', 'm.sc', 'msc', ' m.s', 'mba', 'ms '] as $n) {
                if (mb_strpos($section, $n) !== false) {
                    $degree = 'Master';
                    break;
                }
            }
        }
        if ($degree === null) {
            foreach (['bachelor', 'b.tech', 'btech', 'b.s', 'undergraduate', 'bs '] as $n) {
                if (mb_strpos($section, $n) !== false) {
                    $degree = 'Bachelor';
                    break;
                }
            }
        }
        if ($degree === null) {
            foreach (['diploma', 'associate'] as $n) {
                if (mb_strpos($section, $n) !== false) {
                    $degree = 'Diploma / Associate';
                    break;
                }
            }
        }

        if ($degree === null) {
            return null;
        }

        $fieldHint = null;
        foreach (['computer science', 'information technology', 'software engineering', 'data science', 'business', 'commerce'] as $kw) {
            if (mb_strpos($section, $kw) !== false) {
                $fieldHint = $kw;
                break;
            }
        }

        return $fieldHint ? ($degree.' in '.mb_convert_case($fieldHint, MB_CASE_TITLE, 'UTF-8')) : $degree;
    }

    /**
     * @param  string[]  $jobSkills
     * @return array{score:int,found_projects:bool,keyword_hits:int,keyword_den:int,matched_keywords:list<string>}
     */
    private function scoreProjectsDetail(Job $job, array $jobSkills, string $cvLower): array
    {
        $chunk = $this->extractProjectsChunk($cvLower);
        if ($chunk === null) {
            return ['score' => 0, 'found_projects' => false, 'keyword_hits' => 0, 'keyword_den' => 0, 'matched_keywords' => []];
        }

        $originalNeedles = array_values(array_filter(array_map(
            fn ($s) => trim((string) $s),
            $jobSkills
        )));

        $skillNeedles = array_values(array_filter(array_map(
            fn ($s) => mb_strtolower(trim((string) $s)),
            $jobSkills
        )));

        if ($skillNeedles === []) {
            $derived = $this->deriveSkillsFromJobText($job->title . "\n" . $job->description);
            $originalNeedles = $derived;
            $skillNeedles = array_map(fn ($s) => mb_strtolower(trim((string) $s)), $derived);
        }

        $hits = 0;
        $matchedDisplay = [];
        foreach ($skillNeedles as $idx => $needle) {
            if ($needle === '') {
                continue;
            }
            if ($this->cvContainsSkill($chunk, $needle)) {
                $hits++;
                $label = $originalNeedles[$idx] ?? $needle;
                if (count($matchedDisplay) < 6) {
                    $matchedDisplay[] = $label;
                }
            }
        }

        $den = max(3, min(8, count($skillNeedles)));
        $score = (int) round(min(1.0, $hits / $den) * 100);

        $titleTokens = preg_split('/\s+/', mb_strtolower((string) $job->title)) ?: [];
        $titleTokens = array_values(array_filter($titleTokens, fn ($t) => mb_strlen($t) >= 4));
        $titleHits = 0;
        foreach (array_slice($titleTokens, 0, 5) as $t) {
            if (mb_strpos($chunk, $t) !== false) {
                $titleHits++;
            }
        }
        if ($titleHits >= 1) {
            $score = min(100, $score + 10);
        }

        return [
            'score' => max(0, min(100, $score)),
            'found_projects' => true,
            'keyword_hits' => $hits,
            'keyword_den' => $den,
            'matched_keywords' => $matchedDisplay,
        ];
    }

    /**
     * Projects relevance: look for a "project(s)" section and score overlap with job skills/title.
     *
     * @param  string[]  $jobSkills
     */
    private function scoreProjects(Job $job, array $jobSkills, string $cvLower): int
    {
        $chunk = $this->extractProjectsChunk($cvLower);
        if ($chunk === null) {
            return 0;
        }

        $skillNeedles = array_values(array_filter(array_map(
            fn ($s) => mb_strtolower(trim((string) $s)),
            $jobSkills
        )));

        if ($skillNeedles === []) {
            $skillNeedles = $this->deriveSkillsFromJobText($job->title . "\n" . $job->description);
        }

        $hits = 0;
        foreach ($skillNeedles as $needle) {
            if ($needle === '') {
                continue;
            }
            if ($this->cvContainsSkill($chunk, $needle)) {
                $hits++;
            }
        }

        $den = max(3, min(8, count($skillNeedles)));
        $score = (int) round(min(1.0, $hits / $den) * 100);

        // Small bonus if project chunk references the role/title keywords.
        $titleTokens = preg_split('/\s+/', mb_strtolower((string) $job->title)) ?: [];
        $titleTokens = array_values(array_filter($titleTokens, fn ($t) => mb_strlen($t) >= 4));
        $titleHits = 0;
        foreach (array_slice($titleTokens, 0, 5) as $t) {
            if (mb_strpos($chunk, $t) !== false) {
                $titleHits++;
            }
        }
        if ($titleHits >= 1) {
            $score = min(100, $score + 10);
        }

        return max(0, min(100, $score));
    }

    private function extractProjectsChunk(string $cvLower): ?string
    {
        // Prefer a section-like chunk starting at "project" heading if present.
        if (preg_match('/\bprojects?\b[\s:\-]*\n?([\s\S]{0,1800})/i', $cvLower, $m)) {
            return mb_strtolower(trim($m[0]));
        }

        // Fallback: any occurrence of "project" => use a window around it.
        $pos = mb_strpos($cvLower, 'project');
        if ($pos === false) {
            return null;
        }
        $start = max(0, $pos - 400);
        return mb_substr($cvLower, $start, 1800);
    }

    /**
     * @param  string[]  $requiredSkills
     */
    private function scoreSkills(array $requiredSkills, string $cvLower): int
    {
        return $this->scoreSkillsDetail($requiredSkills, $cvLower)['score'];
    }

    private function cvContainsSkill(string $cvLower, string $needle): bool
    {
        if (mb_strpos($cvLower, $needle) !== false) {
            return true;
        }
        // Simple plural / spacing variants
        $alt = rtrim($needle, 's') . 's';
        if ($alt !== $needle && mb_strpos($cvLower, $alt) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Experience score uses two signals:
     * - Years vs min_experience_years
     * - Field relevance: if CV work experience is not related to the job's field, score becomes 0.
     *
     * @param  string[]  $jobSkills
     */
    private function scoreExperience(Job $job, array $jobSkills, string $cvLower): int
    {
        return $this->scoreExperienceDetail($job, $jobSkills, $cvLower)['score'];
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
     * Best-effort: max of patterns like "3 years", "3+ years", "three years".
     */
    private function extractYearsExperience(string $cvLower): ?float
    {
        $best = null;
        if (preg_match_all('/(\d+(?:\.\d+)?)\s*\+?\s*(?:years?|yrs?|yr)\b/i', $cvLower, $m)) {
            foreach ($m[1] as $n) {
                $v = (float) $n;
                if ($best === null || $v > $best) {
                    $best = $v;
                }
            }
        }

        return $best;
    }

    /**
     * Education score uses two signals:
     * - Level match (e.g. Bachelor vs Master)
     * - Field relevance: if CV education field is not related to the job's field, score becomes 0.
     *
     * @param  string[]  $jobSkills
     */
    private function scoreEducation(Job $job, array $jobSkills, string $cvLower): int
    {
        return $this->scoreEducationDetail($job, $jobSkills, $cvLower)['score'];
    }

    /**
     * If we can infer both job domain and CV education domain, enforce relevance:
     * - For "computing" jobs, non-computing education => 0 (strict requirement).
     * - For other domains, if domains mismatch => 0.
     */
    private function isEducationFieldRelatedToJob(Job $job, array $jobSkills, string $cvLower): bool
    {
        $jobText = trim(implode("\n", array_filter([
            (string) ($job->experience_field ?? ''),
            (string) ($job->education_field ?? ''),
            (string) $job->title,
            is_array($jobSkills) ? implode(' ', $jobSkills) : '',
            (string) $job->description,
        ])));

        $jobDomain = $this->detectDomain(mb_strtolower($jobText));
        $cvEduDomain = $this->detectEducationDomain($cvLower);

        // If we can't confidently infer one side, don't hard-zero.
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
        // Try to focus on education-ish context first.
        $haystack = $cvLower;
        if (preg_match('/(education|qualification|degree)[\s\S]{0,800}/i', $cvLower, $m)) {
            $haystack = mb_strtolower($m[0]);
        }

        return $this->detectDomain($haystack);
    }

    /**
     * Very small, deterministic domain classifier (keyword-based).
     *
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

        // Order matters: some terms overlap.
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

    private function educationRank(?string $level): ?int
    {
        if ($level === null || trim($level) === '') {
            return null;
        }
        $l = mb_strtolower(trim($level));
        if (str_contains($l, 'phd') || str_contains($l, 'doctor')) {
            return 6;
        }
        if (str_contains($l, 'master') || str_contains($l, 'm.s') || str_contains($l, 'ms ') || str_contains($l, 'mba')) {
            return 5;
        }
        if (str_contains($l, 'bachelor') || str_contains($l, 'b.s') || str_contains($l, 'bs ') || str_contains($l, 'b.tech') || str_contains($l, 'undergraduate')) {
            return 4;
        }
        if (str_contains($l, 'diploma') || str_contains($l, 'associate')) {
            return 3;
        }
        if (str_contains($l, 'high school') || str_contains($l, 'secondary')) {
            return 2;
        }

        return 3;
    }

    private function detectHighestEducationRank(string $cvLower): ?int
    {
        $r = null;
        $checks = [
            6 => ['ph.d', 'phd', 'doctor of', 'doctorate'],
            5 => ['master', 'm.s', ' m.s', 'mba', 'msc', 'm.sc'],
            4 => ['bachelor', 'b.s', ' b.s', 'bs ', 'b.tech', 'btech', 'undergraduate degree'],
            3 => ['diploma', 'associate degree'],
            2 => ['high school', 'hssc', 'intermediate'],
        ];
        foreach ($checks as $rank => $needles) {
            foreach ($needles as $n) {
                if (mb_strpos($cvLower, $n) !== false) {
                    $r = max($r ?? 0, $rank);
                }
            }
        }

        return $r;
    }

    /**
     * @param array{score:int,hits:int,total:int,required:list<string>,matched:list<string>,missing:list<string>} $skills
     * @param array{score:int,years:?float,required_years:?int,related:bool,job_field:?string} $experience
     * @param array{score:int,required_level:?string,detected_rank:?int,required_rank:?int,related:bool,job_requirement_text:?string,applicant_label:?string} $education
     * @param array{score:int,found_projects:bool,keyword_hits:int,keyword_den:int,matched_keywords:list<string>} $projects
     */
    private function buildSummary(array $skills, array $experience, array $education, array $projects, int $overall, Job $job): string
    {
        $lines = [];

        $lines[] = '- Overall: '.$overall.'% — component scores: Skills '.$skills['score'].'%, Experience '.$experience['score'].'%, Education '.$education['score'].'%, Projects '.$projects['score'].'% (weights 40/30/20/10).';

        if (($skills['total'] ?? 0) > 0) {
            $reqList = implode(', ', array_slice($skills['required'] ?? [], 0, 12));
            if (count($skills['required'] ?? []) > 12) {
                $reqList .= '…';
            }
            $skillBits = [
                "Required for this job: {$reqList}",
                "CV explicitly mentions {$skills['hits']}/{$skills['total']}",
            ];
            if (count($skills['missing'] ?? []) > 0) {
                $skillBits[] = 'Not found in CV: '.implode(', ', array_slice($skills['missing'], 0, 8)).(count($skills['missing']) > 8 ? '…' : '');
            }
            if (count($skills['matched'] ?? []) > 0) {
                $skillBits[] = 'Found in CV: '.implode(', ', array_slice($skills['matched'], 0, 8)).(count($skills['matched']) > 8 ? '…' : '');
            }
            $lines[] = '- Skills ('.$skills['score'].'%): '.implode('; ', $skillBits).'.';
        } else {
            $lines[] = '- Skills: no explicit required skills list on the job; score uses inferred keywords from the job text.';
        }

        if (($job->min_experience_years ?? 0) > 0) {
            $reqField = $experience['job_field'] ?? null;
            $fieldBit = $reqField ? "; job expects experience related to: {$reqField}" : '';
            if (($experience['years'] ?? null) === null) {
                $lines[] = '- Experience ('.$experience['score'].'%): job asks for '.$job->min_experience_years.'+ year(s)'.$fieldBit.'; CV did not clearly state years of experience.';
            } elseif (($experience['related'] ?? true) === false) {
                $y = (float) ($experience['years'] ?? 0);
                $lines[] = '- Experience ('.$experience['score'].'%): job asks for '.$job->min_experience_years.'+ year(s) in a related field'.$fieldBit.'; CV suggests ~'.$y.' year(s) but work history does not match the job field (0%).';
            } else {
                $y = (float) ($experience['years'] ?? 0);
                $lines[] = '- Experience ('.$experience['score'].'%): job asks for '.$job->min_experience_years.'+ year(s)'.$fieldBit.'; CV suggests ~'.$y.' year(s) in a related area.';
            }
        }

        $jobEdu = $education['job_requirement_text'] ?? null;
        if ($jobEdu) {
            $appEdu = $education['applicant_label'] ?? null;
            if (($education['detected_rank'] ?? null) === null) {
                $lines[] = '- Education ('.$education['score'].'%): job requires '.$jobEdu.'; CV did not clearly show readable degree/education lines.';
            } elseif (($education['related'] ?? true) === false) {
                $app = $appEdu ?: ($this->rankToEducationLabel($education['detected_rank'] ?? null) ?? 'detected level');
                $lines[] = '- Education ('.$education['score'].'%): job requires '.$jobEdu.'; CV indicates '.$app.', but field does not match the job domain (0%).';
            } else {
                $reqR = (int) ($education['required_rank'] ?? 0);
                $cvR = (int) ($education['detected_rank'] ?? 0);
                $app = $appEdu ?: ($this->rankToEducationLabel($cvR) ?? 'detected level');
                if ($cvR >= $reqR) {
                    $lines[] = '- Education ('.$education['score'].'%): job requires '.$jobEdu.'; CV indicates '.$app.', which meets or exceeds the required level.';
                } else {
                    $lines[] = '- Education ('.$education['score'].'%): job requires '.$jobEdu.'; CV indicates '.$app.', which is below the required level.';
                }
            }
        }

        if (($projects['found_projects'] ?? false) === false) {
            $lines[] = '- Projects ('.$projects['score'].'%): no projects/portfolio section or “project” mentions found in the CV.';
        } else {
            $mk = $projects['matched_keywords'] ?? [];
            $kwNote = count($mk) ? '; examples in project text: '.implode(', ', $mk) : '';
            $lines[] = '- Projects ('.$projects['score'].'%): matched '.$projects['keyword_hits'].'/'.$projects['keyword_den'].' job keywords in the projects area'.$kwNote.'.';
        }

        return implode("\n", array_values(array_filter($lines)));
    }

    /**
     * @return string[]
     */
    private function deriveSkillsFromJobText(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9\+\#\.\s]/u', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/', $text) ?: [];

        $stop = array_flip([
            'the', 'and', 'for', 'with', 'you', 'your', 'will', 'this', 'that', 'from', 'are', 'our', 'job', 'role', 'work', 'must', 'have',
            'to', 'of', 'in', 'on', 'at', 'a', 'an', 'as', 'is', 'be', 'by', 'or', 'we', 'they', 'it', 'their', 'them', 'can', 'may', 'not',
            'required', 'requirement', 'requirements', 'experience', 'years', 'year', 'skills', 'skill', 'education', 'level',
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

        return array_values(array_keys(array_slice($freq, 0, 15, true)));
    }
}
