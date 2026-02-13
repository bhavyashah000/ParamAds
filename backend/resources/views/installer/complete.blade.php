@extends('layouts.app')
@section('title', 'Installation Complete')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check-circle text-green-500 text-4xl"></i>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 mb-2">Installation Complete!</h1>
        <p class="text-gray-500 mb-8">ParamAds has been successfully installed and configured.</p>

        <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
            <h3 class="font-semibold text-gray-700 mb-3">What's Next?</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-1"></i>
                    <span>Log in to the <strong>Admin Dashboard</strong> to configure API keys and manage settings.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-1"></i>
                    <span>Connect your <strong>Meta Ads</strong> and <strong>Google Ads</strong> accounts.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-1"></i>
                    <span>Create your first ad campaign with geographic targeting.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-1"></i>
                    <span>Set up automation rules to optimize your campaigns.</span>
                </li>
            </ul>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 mb-6 text-left text-sm text-yellow-700">
            <i class="fas fa-shield-alt mr-2"></i>
            <strong>Security:</strong> For production, delete or restrict access to the installer routes. The installer is automatically disabled after installation.
        </div>

        <div class="flex gap-4">
            <a href="/login" class="flex-1 bg-primary text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-sign-in-alt mr-2"></i> Go to Login
            </a>
            <a href="/admin" class="flex-1 bg-gray-800 text-white py-3 rounded-lg font-semibold hover:bg-gray-900 transition">
                <i class="fas fa-cog mr-2"></i> Admin Panel
            </a>
        </div>
    </div>
</div>
@endsection
