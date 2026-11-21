@extends('layouts.header')
@section('title', 'Users')

@section('content')
    <p class="mb-5 text-sm text-stone-600">Check identification before making a decision.</p>
    <div class="overflow-x-auto rounded-xl border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-red-50 text-xs font-semibold tracking-wide text-red-900">
                <tr>
                    <th scope="col" class="px-4 py-3">Name</th>
                    <th scope="col" class="px-4 py-3">NIK</th>
                    <th scope="col" class="px-4 py-3">Phone</th>
                    <th scope="col" class="px-4 py-3">Email</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($users as $user)
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50">
                        <th scope="row" class="px-4 py-4 font-semibold">{{ $user->name }}</th>
                        <td class="px-4 py-4 text-stone-700">{{ $user->nik }}</td>
                        <td class="px-4 py-4 text-stone-700">{{ $user->phone }}</td>
                        <td class="px-4 py-4 text-stone-700">{{ $user->email }}</td>
                        <td class="px-4 py-4 text-stone-700">
                            <span @class([
                                'inline-block rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-green-50 text-green-800' => $user->approval_status === 'approved',
                                'bg-red-50 text-red-800' => $user->approval_status === 'rejected',
                                'bg-amber-50 text-amber-900' => ! in_array($user->approval_status, ['approved', 'rejected'], true),
                            ])>{{ match ($user->approval_status) {
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                default => 'Pending',
                            } }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <form class="flex justify-end gap-2" method="post" action="{{ route('registration.verify', $user) }}">
                                @csrf
                                @method('PATCH')
                                @if ($user->approval_status !== 'approved')
                                    <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit" name="decision" value="approved">Approve</button>
                                @endif
                                @if ($user->approval_status !== 'rejected')
                                    <button class="inline-block rounded-lg border border-red-200 px-3 py-2 text-sm font-medium whitespace-nowrap text-red-800 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit" name="decision" value="rejected">Reject</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-10 text-center text-stone-600" colspan="6">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="mt-5">{{ $users->links() }}</div>
    @endif
@endsection
