@extends('layouts.admin')
@section('page-title', 'User Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">All Users</h3>
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary">
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3">ID</th>
                <th class="pb-3">Name</th>
                <th class="pb-3">Email</th>
                <th class="pb-3">Organization</th>
                <th class="pb-3">Role</th>
                <th class="pb-3">Admin</th>
                <th class="pb-3">Joined</th>
                <th class="pb-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3">{{ $user->id }}</td>
                <td class="py-3 font-medium">{{ $user->name }}</td>
                <td class="py-3 text-gray-500">{{ $user->email }}</td>
                <td class="py-3">{{ $user->organization->name ?? '-' }}</td>
                <td class="py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $user->role }}</span></td>
                <td class="py-3">
                    @if($user->is_admin)
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Admin</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="py-3 text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                <td class="py-3">
                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="text-primary hover:text-indigo-700"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="py-8 text-center text-gray-400">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
