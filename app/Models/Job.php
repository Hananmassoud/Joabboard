<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'required_skills',
        'min_experience_years',
        'experience_field',
        'education_level',
        'education_field',
        'location',
        'salary',
        'job_type',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'min_experience_years' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function favouritedByApplicants()
    {
        return $this->belongsToMany(User::class, 'job_favourites')->withTimestamps();
    }
}

