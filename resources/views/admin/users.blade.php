@extends('layouts.account')
@section('title', 'Users')

@section('content')
    <p class="my-3 text-sm leading-6">Check identification before making a decision.</p>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead><tr><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Name</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">NIK</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Phone</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Email</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Status</th><th class="border-b border-stone-200 bg-stone-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $user->nik }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $user->phone }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $user->email }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ match ($user->approval_status) {
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => 'Pending',
                        } }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">
                            <form method="post" action="{{ route('registration.verify', $user) }}">
                                @csrf
                                @method('PATCH')
                                @if ($user->approval_status !== 'approved')
                                    <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" name="decision" value="approved">Approve</button>
                                @endif
                                @if ($user->approval_status !== 'rejected')
                                    <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" name="decision" value="rejected">Reject</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="6">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
