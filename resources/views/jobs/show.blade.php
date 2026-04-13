{{-- Single job page: JobController@show. Applicants can apply (POST jobs.apply) with PDF CV; AI runs in background job. --}}
@extends('layout.main')

@section('title', $job->title)

@section('content')
<main class="jb-job-show-page">
    <div class="job-post-company">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-xl-7 col-lg-8">
                    <div class="single-job-items job-card-pro jb-job-show-header">
                        <div class="job-items">
                            <div class="company-img company-logo-sm">
                                @php
                                    $jobLogo = $job->company?->company_logo;
                                @endphp
                                <a href="#">
                                    <img src="{{ $jobLogo ? asset('storage/' . $jobLogo) : asset('assets/img/icon/job-list1.png') }}" alt="{{ $job->company?->company_name ?? $job->company?->name ?? 'Company' }} logo">
                                </a>
                            </div>
                            <div class="job-tittle">
                                <a href="#"><h4>{{ $job->title }}</h4></a>
                                <ul>
                                    <li>{{ $job->company?->company_name ?? $job->company?->name }}</li>
                                    @if($job->location)
                                        <li><i class="fas fa-map-marker-alt"></i>{{ $job->location }}</li>
                                    @endif
                                    @if($job->salary)
                                        <li>{{ $job->salary }}</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="job-post-details">
                        {{-- Main job text from the company --}}
                        <section class="dashboard-block jb-job-section">
                            <div class="jb-section-head">
                                <h4 class="jb-section-title mb-0">Job Description</h4>
                            </div>
                            <div class="jb-section-body">
                                <p class="mb-0">{{ $job->description }}</p>
                            </div>
                        </section>

                        {{-- Structured requirements (also used by AI matcher) --}}
                        @if(!empty($job->required_skills) || $job->min_experience_years || $job->experience_field || $job->education_level || $job->education_field)
                            <section class="dashboard-block jb-job-section">
                                <div class="jb-section-head">
                                    <h4 class="jb-section-title mb-0">Requirements</h4>
                                </div>
                                <div class="jb-section-body">
                                    <div class="jb-kv-list">
                                        @if(!empty($job->required_skills))
                                            <div class="jb-kv">
                                                <div class="jb-kv__k">Skills</div>
                                                <div class="jb-kv__v">
                                                    {{-- Skills may be stored as array or as comma/newline text --}}
                                                    @php
                                                        $skills = is_array($job->required_skills)
                                                            ? $job->required_skills
                                                            : preg_split('/,|\n/', (string) $job->required_skills);
                                                        $skills = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $skills ?: [])));
                                                    @endphp
                                                    <div class="jb-pill-row">
                                                        @foreach($skills as $skill)
                                                            <span class="jb-pill">{{ $skill }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($job->min_experience_years)
                                            <div class="jb-kv">
                                                <div class="jb-kv__k">Minimum experience</div>
                                                <div class="jb-kv__v">{{ $job->min_experience_years }} year(s)</div>
                                            </div>
                                        @endif
                                        @if($job->experience_field)
                                            <div class="jb-kv">
                                                <div class="jb-kv__k">Recent experience field</div>
                                                <div class="jb-kv__v">{{ $job->experience_field }}</div>
                                            </div>
                                        @endif
                                        @if($job->education_level)
                                            <div class="jb-kv">
                                                <div class="jb-kv__k">Education level</div>
                                                <div class="jb-kv__v">{{ $job->education_level }}</div>
                                            </div>
                                        @endif
                                        @if($job->education_field)
                                            <div class="jb-kv">
                                                <div class="jb-kv__k">Education field</div>
                                                <div class="jb-kv__v">{{ $job->education_field }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @endif

                        {{-- Only applicants apply; guests see login prompt --}}
                        @auth
                            @if(auth()->user()->isApplicant())
                                {{-- Already applied: show status + employer “reviewed” timestamp if set --}}
                                @if(!empty($existingApplication))
                                    <section class="dashboard-block jb-job-section">
                                        <div class="jb-section-head">
                                            <h4 class="jb-section-title mb-0">Your application</h4>
                                        </div>

                                        @if (session('status'))
                                            <div class="alert alert-success">
                                                {{ session('status') }}
                                            </div>
                                        @endif

                                        <p class="mb-2">
                                            <strong>Status:</strong>
                                            <span class="app-status app-status-{{ strtolower($existingApplication->status) }}">{{ ucfirst($existingApplication->status) }}</span>
                                        </p>
                                        <p class="text-muted small mb-3">Submitted {{ $existingApplication->created_at->diffForHumans() }}</p>

                                        @if($existingApplication->company_read_at)
                                            <div class="alert alert-success mb-0 applicant-job-reviewed-alert">
                                                <strong>Your application has been reviewed by the employer.</strong>
                                                <span class="d-block small mt-1 mb-0">{{ $existingApplication->company_read_at->format('M j, Y \a\t g:i A') }}</span>
                                            </div>
                                        @else
                                            <p class="text-muted mb-0">The employer has not marked your application as reviewed yet. You will see a confirmation here when they do.</p>
                                        @endif
                                    </section>
                                @else
                                    {{-- New application: PDF CV required for AI text extraction + matching --}}
                                    <section class="dashboard-block jb-job-section">
                                        <div class="jb-section-head">
                                            <h4 class="jb-section-title mb-0">Apply for this job</h4>
                                        </div>

                                        @if (session('status'))
                                            <div class="alert alert-success">
                                                {{ session('status') }}
                                            </div>
                                        @endif

                                        @if ($errors->any())
                                            <div class="alert alert-danger">
                                                <ul>
                                                    @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        {{-- multipart: file upload --}}
                                        <form method="POST" action="{{ route('jobs.apply', $job) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="single-input mb-15">
                                                        <label for="applicant_name">Full name</label>
                                                        <input type="text" name="applicant_name" id="applicant_name" class="form-control"
                                                               value="{{ old('applicant_name', auth()->user()->name) }}" required autocomplete="name">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="single-input mb-15">
                                                        <label for="applicant_email">Email</label>
                                                        <input type="email" name="applicant_email" id="applicant_email" class="form-control"
                                                               value="{{ old('applicant_email', auth()->user()->email) }}" required autocomplete="email">
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="single-input mb-15">
                                                        <label for="cover_letter">Cover Letter (optional)</label>
                                                        <textarea name="cover_letter" id="cover_letter" cols="30" rows="5" class="form-control" placeholder="Write a short cover letter...">{{ old('cover_letter') }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="single-input mb-15">
                                                        <label for="cv_url">CV URL (optional)</label>
                                                        <input type="url" name="cv_url" id="cv_url" class="form-control" placeholder="Link to your CV (Google Drive, etc.)" value="{{ old('cv_url') }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="single-input mb-15">
                                                        <label for="cv">Upload CV (PDF)</label>
                                                        <input type="file" name="cv" id="cv" class="form-control" accept=".pdf,application/pdf" required>
                                                        <small class="text-muted">Allowed: PDF (max 5MB)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn head-btn2">Submit Application</button>
                                        </form>
                                    </section>
                                @endif
                            @endif
                        @else
                            <p>Please <a href="{{ route('login') }}">login</a> as an applicant to apply for this job.</p>
                        @endauth
                    </div>
                </div>

                {{-- Sidebar summary (date, type, location, salary) --}}
                <div class="col-xl-4 col-lg-4">
                    <aside class="dashboard-block jb-job-overview">
                        <div class="jb-section-head">
                            <h4 class="jb-section-title mb-0">Job Overview</h4>
                        </div>
                        <div class="jb-section-body">
                            <div class="jb-kv-list jb-kv-list--compact">
                                <div class="jb-kv">
                                    <div class="jb-kv__k">Posted</div>
                                    <div class="jb-kv__v">{{ $job->created_at->format('M d, Y') }}</div>
                                </div>
                                @if($job->job_type)
                                    <div class="jb-kv">
                                        <div class="jb-kv__k">Type</div>
                                        <div class="jb-kv__v">{{ $job->job_type }}</div>
                                    </div>
                                @endif
                                @if($job->location)
                                    <div class="jb-kv">
                                        <div class="jb-kv__k">Location</div>
                                        <div class="jb-kv__v">{{ $job->location }}</div>
                                    </div>
                                @endif
                                @if($job->salary)
                                    <div class="jb-kv">
                                        <div class="jb-kv__k">Salary</div>
                                        <div class="jb-kv__v">{{ $job->salary }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

