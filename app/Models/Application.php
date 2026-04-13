<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'applicant_name',
        'applicant_email',
        'cover_letter',
        'cv_url',
        'cv_path',
        'cv_file',
        'status',
        'company_read_at',
        'ai_status',
        'ai_job_field',
        'skills_match',
        'experience_match',
        'ai_relevant_experience_years',
        'education_match',
        'projects_match',
        'overall_match',
        'ai_summary',
        'cv_text',
        'ai_extracted_skills',
        'ai_processed_at',
        'ai_error',
    ];

    protected $casts = [
        'skills_match' => 'integer',
        'experience_match' => 'integer',
        'ai_relevant_experience_years' => 'decimal:1',
        'education_match' => 'integer',
        'projects_match' => 'integer',
        'overall_match' => 'integer',
        'ai_processed_at' => 'datetime',
        'company_read_at' => 'datetime',
        'ai_extracted_skills' => 'array',
    ];

    public function isReadByCompany(): bool
    {
        return $this->company_read_at !== null;
    }

    public function markReadByCompany(): void
    {
        if ($this->company_read_at === null) {
            $this->forceFill(['company_read_at' => now()])->save();
        }
    }

    public function markUnreadByCompany(): void
    {
        $this->forceFill(['company_read_at' => null])->save();
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

