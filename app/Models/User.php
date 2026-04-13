<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_name',
        'company_logo',
        'company_established_year',
        'company_established_date',
        'company_website',
        'company_description',
        'company_notifications_last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'company_established_year' => 'integer',
        'company_established_date' => 'date',
        'company_notifications_last_seen_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function isCompany(): bool
    {
        return $this->role === 'company';
    }

    public function isApplicant(): bool
    {
        return $this->role === 'applicant';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Jobs the applicant saved (favourites) from the job board / home page.
     */
    public function favouriteJobs()
    {
        return $this->belongsToMany(Job::class, 'job_favourites')->withTimestamps();
    }
}
