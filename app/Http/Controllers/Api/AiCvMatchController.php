<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiMatching\OpenAiSemanticCvMatcher;
use Illuminate\Http\Request;

class AiCvMatchController extends Controller
{
    public function __invoke(Request $request, OpenAiSemanticCvMatcher $matcher)
    {
        $data = $request->validate([
            'job_description' => ['required', 'string'],
            'cv_text' => ['required', 'string'],
        ]);

        $result = $matcher->match([
            'job_description' => $data['job_description'],
            'cv_text' => $data['cv_text'],
        ]);

        // Ensure API response is JSON only with the exact keys requested.
        return response()->json([
            'job_field' => $result['job_field'],
            'relevant_experience_years' => $result['relevant_experience_years'],
            'skills_match' => $result['skills_match'],
            'experience_match' => $result['experience_match'],
            'education_match' => $result['education_match'],
            'projects_match' => $result['projects_match'],
            'overall_match' => $result['overall_match'],
            'reasoning' => $result['reasoning'],
        ]);
    }
}

