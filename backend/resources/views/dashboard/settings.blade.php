@extends('layouts.dashboard')
@section('page-title', 'Settings')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Profile Settings</h3>
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep)</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <button type="submit" class="mt-4 bg-primary text-white px-6 py-2 rounded-lg hover:bg-indigo-700">Save Changes</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Organization</h3>
        <div class="space-y-2 text-sm">
            <p><span class="text-gray-500">Name:</span> {{ auth()->user()->organization->name ?? '-' }}</p>
            <p><span class="text-gray-500">Plan:</span> <span class="capitalize">{{ auth()->user()->organization->plan ?? '-' }}</span></p>
            <p><span class="text-gray-500">Role:</span> <span class="capitalize">{{ auth()->user()->role }}</span></p>
        </div>
    </div>
</div>
@endsection
