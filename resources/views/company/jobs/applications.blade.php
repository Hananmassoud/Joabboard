{{-- One job’s applicant list: match scores from ProcessApplicationAiMatch; filters by min score and read/unread. --}}
@extends('layout.main')

@section('title', 'Applications - ' . $job->title)

@section('content')
<main class="company-panel-page company-applications-page jb-panel-page">
    <div class="container">
        <div class="row mb-4 mb-lg-5 align-items-start">
            <div class="col-lg-8">
                <div class="section-tittle mb-0">
                    <span>Applications</span>
                    <h2>{{ $job->title }}</h2>
                </div>
            </div>
            <div class="col-lg-4 text-right mt-30 mt-lg-0">
                <a href="{{ route('company.jobs.index') }}" class="btn head-btn1">Back to Jobs</a>
            </div>
        </div>

        {{-- Match weights from config (same as server-side overall score) --}}
        @php
            $wS = (float) config('services.matching.weight_skills', 0.4);
            $wE = (float) config('services.matching.weight_experience', 0.3);
            $wEd = (float) config('services.matching.weight_education', 0.2);
            $wP = (float) config('services.matching.weight_projects', 0.1);
            $wSp = (int) round($wS * 100);
            $wEp = (int) round($wE * 100);
            $wEdp = (int) round($wEd * 100);
            $wPp = (int) round($wP * 100);
        @endphp
        <div class="row mb-2">
            <div class="col-12">
                <p class="text-muted small mb-0">
                    Match score uses fixed weights: <strong>{{ $wSp }}% skills</strong>,
                    <strong>{{ $wEp }}% experience</strong>,
                    <strong>{{ $wEdp }}% education</strong>,
                    <strong>{{ $wPp }}% projects</strong>.
                    Overall = weighted average of the four percentages (same CV + job ⇒ same result when recalculated).
                </p>
            </div>
        </div>

        {{-- Query string filters: controller rebuilds URL via $applicationsUrl --}}
        <div class="row mb-3">
            <div class="col-lg-12 d-flex flex-wrap align-items-center">
                <span class="text-muted mr-3 mb-2">Filter by match:</span>
                <a class="btn btn-sm {{ empty($minMatch) ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['min_match' => null]) }}">All</a>
                <a class="btn btn-sm {{ (int) $minMatch === 60 ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['min_match' => 60]) }}">60%+</a>
                <a class="btn btn-sm {{ (int) $minMatch === 70 ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['min_match' => 70]) }}">70%+</a>
                <a class="btn btn-sm {{ (int) $minMatch === 80 ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['min_match' => 80]) }}">80%+</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-lg-12 d-flex flex-wrap align-items-center">
                <span class="text-muted mr-3 mb-2">Review status:</span>
                <a class="btn btn-sm {{ empty($readFilter) ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['read' => null]) }}">All</a>
                <a class="btn btn-sm {{ ($readFilter ?? '') === 'unread' ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['read' => 'unread']) }}">Unread</a>
                <a class="btn btn-sm {{ ($readFilter ?? '') === 'read' ? 'head-btn2' : 'head-btn1' }} mr-2 mb-2"
                   href="{{ $applicationsUrl(['read' => 'read']) }}">Read</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="dashboard-block">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Each card: applicant info, AI match breakdown, CV links, recalculate route --}}
                @forelse ($applications as $application)
                    <div class="app-job-card mb-3 {{ $application->company_read_at ? 'app-job-card--reviewed' : 'app-job-card--unread' }}" data-application-id="{{ $application->id }}" data-ai-status="{{ $application->ai_status }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1">{{ $application->applicant_name ?: $application->applicant->name }}</h5>
                                <p class="mb-0 text-muted">{{ $application->applicant_email ?: $application->applicant->email }}</p>
                            </div>
                            <div class="text-md-right">
                                @if(!$application->company_read_at)
                                    <span class="badge badge-primary mb-1">To review</span>
                                @else
                                    <span class="badge badge-light border mb-1">Reviewed</span>
                                @endif
                                <br>
                                <span class="app-status app-status-{{ strtolower($application->status) }}">{{ ucfirst($application->status) }}</span>
                                <div class="small text-muted mt-1">{{ $application->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <strong>Match score:</strong>
                            <span class="text-muted">
                                @if($application->ai_status === 'completed' && !is_null($application->overall_match))
                                    <div class="match-breakdown-card border rounded p-3 mt-2 bg-light">
                                        <p class="small text-uppercase text-muted mb-2 font-weight-bold">Aspect scores (each 0–100%)</p>
                                        <ul class="list-unstyled mb-3 small">
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Education <span class="text-muted">(counts {{ $wEdp }}% toward overall)</span></span>
                                                <strong class="ai-edu-val">{{ $application->education_match ?? '—' }}%</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Experience <span class="text-muted">(counts {{ $wEp }}% toward overall)</span></span>
                                                <strong class="ai-exp-val">{{ $application->experience_match ?? '—' }}%</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Projects <span class="text-muted">(counts {{ $wPp }}% toward overall)</span></span>
                                                <strong class="ai-projects-val">{{ $application->projects_match ?? '—' }}%</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1">
                                                <span>Skills <span class="text-muted">(counts {{ $wSp }}% toward overall)</span></span>
                                                <strong class="ai-skills-val">{{ $application->skills_match ?? '—' }}%</strong>
                                            </li>
                                        </ul>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top">
                                            <div>
                                                <span class="text-uppercase small text-muted font-weight-bold">Overall match</span>
                                                <div class="text-muted small ai-calc-hint">Weighted: (Skills × {{ $wSp }}%) + (Experience × {{ $wEp }}%) + (Education × {{ $wEdp }}%) + (Projects × {{ $wPp }}%)</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="ai-overall-match h4 mb-0 theme-color">{{ $application->overall_match }}%</span>
                                                @if($application->overall_match >= 80)
                                                    <span class="badge badge-success ml-2 align-middle">Top Candidate</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @elseif($application->ai_status === 'failed')
                                    @if(($application->ai_error ?? '') === 'OPENAI_API_KEY is not configured.')
                                        <span class="text-warning">Match unavailable</span>
                                        <span class="text-muted small d-block">Re-run match after fixing configuration.</span>
                                    @else
                                        <span class="text-danger">Failed</span>
                                        @if($application->ai_error)
                                            <span class="text-muted small d-block">Reason: {{ \Illuminate\Support\Str::limit($application->ai_error, 120) }}</span>
                                        @endif
                                    @endif
                                @else
                                    <div class="match-breakdown-card match-breakdown--pending border rounded p-3 mt-2 bg-light">
                                        <p class="small text-uppercase text-muted mb-2 font-weight-bold">Aspect scores (each 0–100%)</p>
                                        <ul class="list-unstyled mb-3 small">
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Education <span class="text-muted">(counts {{ $wEdp }}% toward overall)</span></span>
                                                <strong class="ai-edu-val">—</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Experience <span class="text-muted">(counts {{ $wEp }}% toward overall)</span></span>
                                                <strong class="ai-exp-val">—</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>Projects <span class="text-muted">(counts {{ $wPp }}% toward overall)</span></span>
                                                <strong class="ai-projects-val">—</strong>
                                            </li>
                                            <li class="d-flex justify-content-between align-items-center py-1">
                                                <span>Skills <span class="text-muted">(counts {{ $wSp }}% toward overall)</span></span>
                                                <strong class="ai-skills-val">—</strong>
                                            </li>
                                        </ul>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top">
                                            <div>
                                                <span class="text-uppercase small text-muted font-weight-bold">Overall match</span>
                                                <div class="text-muted small ai-calc-hint">Calculating…</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="ai-overall-match h4 mb-0 theme-color">—</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="small text-muted mt-1 mb-0 ai-processing-msg">Processing match…</p>
                                @endif
                            </span>
                            @if($application->ai_status === 'completed' && $application->ai_summary)
                                <div class="small text-muted mt-1 ai-summary" style="white-space: pre-line;">{{ $application->ai_summary }}</div>
                            @endif
                        </div>

                        <div class="mb-2">
                            <strong>Cover Letter:</strong>
                            <p class="mb-0 text-muted">{{ $application->cover_letter ?: 'Not provided by applicant.' }}</p>
                        </div>

                        <div class="jb-app-actions">
                            @if(!$application->company_read_at)
                                <form method="POST" action="{{ route('company.jobs.applications.read', ['job' => $job, 'application' => $application]) }}" class="jb-app-actions__item">
                                    @csrf
                                    <button type="submit" class="btn jb-btn jb-btn--primary">Mark as read</button>
                                </form>
                            @else
                                <span class="small text-muted jb-app-actions__meta">
                                    Read {{ $application->company_read_at->format('M j, Y g:i A') }}
                                </span>
                                <form method="POST" action="{{ route('company.jobs.applications.unread', ['job' => $job, 'application' => $application]) }}" class="jb-app-actions__item" onsubmit="return confirm('Mark this application as not reviewed?');">
                                    @csrf
                                    <button type="submit" class="btn jb-btn jb-btn--secondary">Mark as unread</button>
                                </form>
                            @endif
                            @if($application->cv_url)
                                <a href="{{ $application->cv_url }}" target="_blank" rel="noopener" class="btn jb-btn jb-btn--ghost jb-app-actions__item">Open CV URL</a>
                            @endif
                            @if($application->cv_path)
                                <a href="{{ asset('storage/' . $application->cv_path) }}" target="_blank" rel="noopener" class="btn jb-btn jb-btn--ghost jb-app-actions__item">Download CV File</a>
                            @endif
                            @if(in_array($application->ai_status, ['completed', 'failed'], true))
                                <form method="POST" action="{{ route('company.jobs.applications.ai.rerun', ['job' => $job, 'application' => $application]) }}" class="jb-app-actions__item" onsubmit="return confirm('Recalculate match? (same CV and job requirements produce the same scores.)');">
                                    @csrf
                                    <button type="submit" class="btn jb-btn jb-btn--secondary">Recalculate match</button>
                                </form>
                            @endif
                            @if($application->status === 'accepted')
                                <button type="button" class="btn jb-btn jb-btn--primary jb-app-actions__item" disabled>Selected Applicant</button>
                            @else
                                <form method="POST" action="{{ route('company.jobs.applications.select', ['job' => $job, 'application' => $application]) }}" class="jb-app-actions__item">
                                    @csrf
                                    <button type="submit" class="btn jb-btn jb-btn--primary">Select Applicant</button>
                                </form>
                            @endif
                            @if(!$application->cv_url && !$application->cv_path)
                                <span class="text-muted small jb-app-actions__meta">No CV provided.</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="mb-0 text-muted">No applications yet for this job.</p>
                @endforelse
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function () {
        const cards = Array.from(document.querySelectorAll('[data-application-id]'));
        if (!cards.length) return;

        const makeUrl = (id) => {
            const tpl = @json(route('company.jobs.applications.ai', ['job' => $job->id, 'application' => '__ID__']));
            return tpl.replace('__ID__', String(id));
        };

        const pending = () => cards.filter(el => {
            const s = (el.getAttribute('data-ai-status') || '').toLowerCase();
            return s === 'pending' || s === 'processing';
        });

        async function pollOnce() {
            const todo = pending();
            if (!todo.length) return;

            await Promise.all(todo.map(async (card) => {
                const id = card.getAttribute('data-application-id');
                try {
                    const res = await fetch(makeUrl(id), { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data || !data.ai_status) return;

                    card.setAttribute('data-ai-status', data.ai_status);

                    if (data.ai_status !== 'completed') return;
                    if (typeof data.overall_match !== 'number') return;

                    const processingMsg = card.querySelector('.ai-processing-msg');
                    if (processingMsg) processingMsg.remove();
                    card.querySelector('.match-breakdown--pending')?.classList.remove('match-breakdown--pending');

                    const matchEl = card.querySelector('.ai-overall-match');
                    if (matchEl) matchEl.textContent = data.overall_match + '%';

                    const sk = card.querySelector('.ai-skills-val');
                    const ex = card.querySelector('.ai-exp-val');
                    const ed = card.querySelector('.ai-edu-val');
                    if (sk && typeof data.skills_match === 'number') sk.textContent = data.skills_match + '%';
                    if (ex && typeof data.experience_match === 'number') ex.textContent = data.experience_match + '%';
                    if (ed && typeof data.education_match === 'number') ed.textContent = data.education_match + '%';

                    const hint = card.querySelector('.ai-calc-hint');
                    if (hint && data.weights) {
                        const we = Math.round((data.weights.education || 0) * 100);
                        const wx = Math.round((data.weights.experience || 0) * 100);
                        const ws = Math.round((data.weights.skills || 0) * 100);
                        hint.textContent = 'Weighted: (Education × ' + we + '%) + (Experience × ' + wx + '%) + (Skills × ' + ws + '%)';
                    }

                    const summaryEl = card.querySelector('.ai-summary');
                    if (summaryEl && data.ai_summary) summaryEl.textContent = data.ai_summary;
                } catch (e) {
                    // silent retry
                }
            }));
        }

        pollOnce();
        setInterval(pollOnce, 4000);
    })();
</script>
@endsection

