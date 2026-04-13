<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apps = App\Models\Application::query()
    ->orderByDesc('id')
    ->take(15)
    ->get(['id', 'job_id', 'ai_status', 'overall_match', 'cv_path', 'cv_text', 'ai_error']);

$jobIds = $apps->pluck('job_id')->unique()->values();
$jobs = App\Models\Job::query()
    ->whereIn('id', $jobIds)
    ->get(['id', 'title', 'required_skills', 'min_experience_years', 'education_level'])
    ->keyBy('id');

foreach ($apps as $a) {
    $text = (string) ($a->cv_text ?? '');
    $len = strlen($text);
    $hash = $len ? substr(sha1($text), 0, 10) : 'empty';
    $preview = $len ? preg_replace('/\s+/', ' ', substr($text, 0, 120)) : '[empty]';
    $err = $a->ai_error ? preg_replace('/\s+/', ' ', (string) $a->ai_error) : '';

    echo "id={$a->id} job={$a->job_id} ai={$a->ai_status} overall=" . ($a->overall_match ?? 'null') .
        " cv_path={$a->cv_path} cv_len={$len} cv_sha1={$hash}\n";
    if (isset($jobs[$a->job_id])) {
        $j = $jobs[$a->job_id];
        $skills = is_array($j->required_skills) ? implode(', ', $j->required_skills) : (string) $j->required_skills;
        $skills = $skills !== '' ? $skills : '—';
        echo "  job_title={$j->title} | skills={$skills} | min_exp={$j->min_experience_years} | edu={$j->education_level}\n";
    }
    echo "  preview={$preview}\n";
    if ($err !== '') {
        echo "  ai_error={$err}\n";
    }
}

