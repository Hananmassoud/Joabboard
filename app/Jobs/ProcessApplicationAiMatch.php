<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\AiMatching\DeterministicMatchScorer;
use App\Services\AiMatching\OpenAiRecruitmentMatcher;
use App\Services\AiMatching\OpenAiSemanticCvMatcher;
use App\Services\CvParsing\OpenAiCvSkillExtractor;
use App\Services\CvTextExtraction\PdfCvTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProcessApplicationAiMatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $applicationId)
    {
    }

    public function handle(
        PdfCvTextExtractor $extractor,
        DeterministicMatchScorer $deterministic,
        OpenAiRecruitmentMatcher $openAi,
        OpenAiSemanticCvMatcher $semantic,
        OpenAiCvSkillExtractor $skillExtractor
    ): void
    {
        // This job runs AI processing for a single job application:
        // - Extract CV text from PDF
        // - Extract skills list from CV
        // - Calculate match scores for the job vs candidate
        $application = Application::with('job')->find($this->applicationId);
        if (! $application) {
            return;
        }

        if (! $application->cv_path) {
            // If no CV exists, we cannot do AI matching.
            $application->forceFill([
                'ai_status' => 'failed',
                'ai_error' => 'No CV file was uploaded for this application.',
                'ai_processed_at' => now(),
            ])->save();
            return;
        }

        // Mark as processing so UI can show status.
        $application->forceFill([
            'ai_status' => 'processing',
            'ai_error' => null,
        ])->save();

        try {
            // 1) Extract readable text from the uploaded CV PDF.
            $cvText = $extractor->extractFromPublicDiskPath($application->cv_path);

            if ($cvText === '') {
                throw new \RuntimeException('Could not extract text from PDF (empty output).');
            }

            // 2) Extract a detailed skills list from the CV (AI-powered).
            $extractedSkills = $skillExtractor->extract($cvText);

            // 3) Choose the matching engine (deterministic or AI) from config.
            $engine = (string) Config::get('services.matching.engine', 'deterministic');
            $scores = match ($engine) {
                'openai' => $openAi->score($application->job, $cvText),
                'openai_semantic' => $this->semanticScoreForJob($semantic, $application->job->title, (string) $application->job->description, $cvText),
                default => $deterministic->score($application->job, $cvText),
            };

            // Save results so the company can view them without recalculating every page load.
            $updates = [
                'cv_text' => $cvText,
                'ai_extracted_skills' => $extractedSkills,
                'skills_match' => $scores['skills_match'],
                'experience_match' => $scores['experience_match'],
                'education_match' => $scores['education_match'],
                'projects_match' => $scores['projects_match'] ?? null,
                'overall_match' => $scores['overall_match'],
                'ai_summary' => $scores['reasoning'] ?? ($scores['summary'] ?? null),
                'ai_status' => 'completed',
                'ai_processed_at' => now(),
            ];

            // Prevent crashes if a deployment hasn't run DB migrations yet.
            if (Schema::hasColumn('applications', 'ai_job_field')) {
                $updates['ai_job_field'] = $scores['job_field'] ?? null;
            }
            if (Schema::hasColumn('applications', 'ai_relevant_experience_years')) {
                $updates['ai_relevant_experience_years'] = $scores['relevant_experience_years'] ?? null;
            }

            $application->forceFill($updates)->save();
        } catch (\Throwable $e) {
            // If AI fails, we store the error message so the UI can show "Failed" with reason.
            Log::warning('AI match failed for application', [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'error' => $e->getMessage(),
            ]);

            $application->forceFill([
                'ai_status' => 'failed',
                'ai_error' => $e->getMessage(),
                'ai_processed_at' => now(),
            ])->save();
            // Do not rethrow: AI failures should not break the application flow.
            // The error is persisted on the application record for admins/companies to review.
            return;
        }
    }

    /**
     * @return array{
     *   job_field:string,
     *   relevant_experience_years:float,
     *   skills_match:int,
     *   experience_match:int,
     *   education_match:int,
     *   projects_match:int,
     *   overall_match:int,
     *   reasoning:string
     * }
     */
    private function semanticScoreForJob(OpenAiSemanticCvMatcher $semantic, string $jobTitle, string $jobDescription, string $cvText): array
    {
        // Build one combined text for the job so the AI sees both title + description.
        $jobDesc = trim($jobTitle . "\n\n" . $jobDescription);

        return $semantic->match([
            'job_description' => $jobDesc,
            'cv_text' => $cvText,
        ]);
    }
}

