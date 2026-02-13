@extends('layouts.admin')
@section('page-title', 'Organizations')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">All Organizations</h3>
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3">ID</th>
                <th class="pb-3">Name</th>
                <th class="pb-3">Plan</th>
                <th class="pb-3">Users</th>
                <th class="pb-3">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($organizations as $org)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3">{{ $org->id }}</td>
                <td class="py-3 font-medium">{{ $org->name }}</td>
                <td class="py-3"><span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700 capitalize">{{ $org->plan }}</span></td>
                <td class="py-3">{{ $org->users_count }}</td>
                <td class="py-3 text-gray-500">{{ $org->created_at->format('M d, Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-400">No organizations found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $organizations->links() }}</div>
</div>
@endsection
