@extends('layout.main')

@section('title', 'Admin — Companies')

@section('content')
<main class="admin-panel jb-panel-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Company accounts</h1>
                <p class="text-muted mb-0">Remove a company to delete its account and all jobs posted by that company.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn head-btn1 mt-2 mt-md-0">Back to dashboard</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Jobs</th>
                            <th>Joined</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td><strong>{{ $company->company_name ?? $company->name }}</strong></td>
                                <td>{{ $company->name }}</td>
                                <td>{{ $company->email }}</td>
                                <td>{{ $company->jobs_count }}</td>
                                <td>{{ $company->created_at->format('M j, Y') }}</td>
                                <td class="text-right">
                                    <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('This will permanently delete the company account and all of its jobs. Continue?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove company</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No company accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $companies->links() }}
        </div>
    </div>
</main>
@endsection
