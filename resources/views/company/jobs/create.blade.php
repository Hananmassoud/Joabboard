{{-- Create job form: posts to CompanyJobController@store. Extra fields (skills, experience) feed AI matching. --}}
@extends('layout.main')

@section('title', 'Post New Job')

@section('content')
<main class="company-panel-page jb-panel-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <div class="small-section-tittle text-center mb-4">
                    <span>Post a Job</span>
                    <h2>Create New Job</h2>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('company.jobs.store') }}" class="jb-form-panel">
                    @csrf

                    {{-- Core job text shown to applicants --}}
                    <div class="mb-3">
                        <label for="title">Job Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="6" required>{{ old('description') }}</textarea>
                    </div>

                    {{-- Parsed/stored for AI; comma or newline separated --}}
                    <div class="mb-3">
                        <label for="required_skills">Required Skills (comma or new line separated)</label>
                        <textarea id="required_skills" name="required_skills" class="form-control" rows="3" placeholder="e.g. Laravel, MySQL, REST APIs" required>{{ old('required_skills') }}</textarea>
                        <small class="text-muted">Add at least 3 skills. These are used for AI matching.</small>
                    </div>

                    {{-- Experience + education hints used by matching logic --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_experience_years">Minimum experience (years)</label>
                            <input type="number" id="min_experience_years" name="min_experience_years" class="form-control"
                                   value="{{ old('min_experience_years') }}" min="0" max="60" step="1" placeholder="e.g. 2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="education_level">Education level</label>
                            <select id="education_level" name="education_level" class="form-control">
                                @php($edu = old('education_level'))
                                <option value="" @selected(empty($edu))>—</option>
                                <option value="High School" @selected($edu === 'High School')>High School</option>
                                <option value="Diploma" @selected($edu === 'Diploma')>Diploma</option>
                                <option value="Bachelor" @selected($edu === 'Bachelor')>Bachelor</option>
                                <option value="Master" @selected($edu === 'Master')>Master</option>
                                <option value="PhD" @selected($edu === 'PhD')>PhD</option>
                                <option value="Other" @selected($edu === 'Other')>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="experience_field">Recent field of experience <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="experience_field"
                                name="experience_field"
                                class="form-control"
                                value="{{ old('experience_field') }}"
                                list="experience_field_suggestions"
                                placeholder="e.g. Backend Development"
                                required
                            >
                            <datalist id="experience_field_suggestions">
                                <option value="Frontend Development"></option>
                                <option value="Backend Development"></option>
                                <option value="Full Stack Development"></option>
                                <option value="Mobile Development"></option>
                                <option value="DevOps / Cloud"></option>
                                <option value="Data Science / ML"></option>
                                <option value="Data Engineering"></option>
                                <option value="Cybersecurity"></option>
                                <option value="QA / Testing"></option>
                                <option value="UI/UX Design"></option>
                                <option value="Project / Product Management"></option>
                            </datalist>
                            <small class="text-muted d-block mt-1">You can type your own value.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="education_field">Field / degree <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="education_field"
                                name="education_field"
                                class="form-control"
                                value="{{ old('education_field') }}"
                                list="education_field_suggestions"
                                placeholder="e.g. Bachelors in Computer Science"
                                required
                            >
                            <datalist id="education_field_suggestions">
                                <option value="Bachelors in Computer Science"></option>
                                <option value="Bachelors in Information Technology"></option>
                                <option value="Bachelors in Software Engineering"></option>
                                <option value="Master in Computer Science"></option>
                                <option value="Master in Information Technology"></option>
                                <option value="Master in Data Science"></option>
                                <option value="PhD in Computer Science"></option>
                            </datalist>
                            <small class="text-muted d-block mt-1">You can type your own value.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location">Location (optional)</label>
                        <input type="text" id="location" name="location" class="form-control" value="{{ old('location') }}">
                    </div>

                    <div class="mb-3">
                        <label for="salary">Salary (optional)</label>
                        <input type="text" id="salary" name="salary" class="form-control" value="{{ old('salary') }}">
                    </div>

                    <div class="mb-3">
                        <label for="job_type">Job Type (e.g. Full Time, Part Time)</label>
                        <input type="text" id="job_type" name="job_type" class="form-control" value="{{ old('job_type') }}">
                    </div>

                    <button type="submit" class="btn head-btn2 mt-3">Create Job</button>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection

