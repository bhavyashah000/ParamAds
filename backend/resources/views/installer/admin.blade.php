@extends('layouts.app')
@section('title', 'Install - Admin Setup')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-green-500"></div>
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-green-500"></div>
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-primary"></div>
                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">4</div>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Create Admin Account</h1>
        <p class="text-gray-500 mb-6">Set up your administrator account. This will be the super admin with full access.</p>

        <form method="POST" action="{{ route('installer.install') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="admin_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required minlength="8">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="admin_password_confirmation" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>
            </div>

            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-700">
                <i class="fas fa-info-circle mr-2"></i>
                Clicking "Install Now" will run database migrations, create your admin account, and finalize the installation. This may take a minute.
            </div>

            <button type="submit" class="mt-6 w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                <i class="fas fa-rocket mr-2"></i> Install Now
            </button>
        </form>
    </div>
</div>
@endsection
