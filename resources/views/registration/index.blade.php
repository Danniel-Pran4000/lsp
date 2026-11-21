@extends('layouts.account')
@section('title', 'Users')

@section('content')
    <p>Check identification before making a decision.</p>
    <div class="table">
        <table>
            <thead><tr><th>Name</th><th>NIK</th><th>Phone</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->nik }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ match ($user->approval_status) {
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => 'Pending',
                        } }}</td>
                        <td>
                            <form method="post" action="{{ route('registration.verify', $user) }}">
                                @csrf
                                @method('PATCH')
                                @if ($user->approval_status !== 'approved')
                                    <button name="decision" value="approved">Approve</button>
                                @endif
                                @if ($user->approval_status !== 'rejected')
                                    <button name="decision" value="rejected">Reject</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
