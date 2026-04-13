{{--
    Save / unsave job for the applicant dashboard shortlist.
    Route: POST jobs.favourite.toggle → JobFavouriteController (pivot on user ↔ job).
    Parent partial passes $job and $isSaved (true if job id is in $favouriteJobIds).
    Guests see nothing; companies/admins see nothing here.
--}}
@auth
    @if(auth()->user()->isApplicant())
        {{-- $saved drives button CSS (outline vs filled heart) --}}
        @php
            $saved = $isSaved ?? false;
        @endphp
        <form method="POST" action="{{ route('jobs.favourite.toggle', $job) }}" class="job-favourite-form d-inline-block">
            @csrf
            <button type="submit" class="btn btn-sm job-favourite-btn {{ $saved ? 'job-favourite-btn--saved' : 'job-favourite-btn--outline' }}" title="{{ $saved ? 'Remove from saved jobs' : 'Save to your dashboard' }}">
                <i class="{{ $saved ? 'fas fa-heart' : 'far fa-heart' }}" aria-hidden="true"></i>
                <span class="ml-1">{{ $saved ? 'Saved' : 'Save Job' }}</span>
            </button>
        </form>
    @endif
@endauth
