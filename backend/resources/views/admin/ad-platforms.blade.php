@extends('layouts.admin')
@section('page-title', 'Ad Platform Settings')

@section('content')
<form method="POST" action="{{ route('admin.ad-platforms.update') }}">
    @csrf @method('PUT')

    <!-- Meta Ads -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-facebook text-blue-600 text-2xl"></i> Meta (Facebook/Instagram) Ads
        </h3>
        <p class="text-sm text-gray-500 mb-4">Create a Meta App at <a href="https://developers.facebook.com/apps" target="_blank" class="text-primary hover:underline">developers.facebook.com</a>. Enable Marketing API and add OAuth redirect URI.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">App ID</label>
                <input type="text" name="meta_app_id" value="{{ $settings['meta_app_id'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">App Secret</label>
                <input type="password" name="meta_app_secret" placeholder="Enter new secret to update" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['meta_app_secret'] ?: 'Not set' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">OAuth Redirect URI</label>
                <input type="text" name="meta_redirect_uri" value="{{ $settings['meta_redirect_uri'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Add this URL to your Meta App's Valid OAuth Redirect URIs.</p>
            </div>
        </div>
    </div>

    <!-- Google Ads -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-google text-red-500 text-2xl"></i> Google Ads
        </h3>
        <p class="text-sm text-gray-500 mb-4">Set up OAuth credentials at <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-primary hover:underline">Google Cloud Console</a>. Apply for a Developer Token at <a href="https://ads.google.com/home/tools/manager-accounts/" target="_blank" class="text-primary hover:underline">Google Ads Manager</a>.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Client ID</label>
                <input type="text" name="google_ads_client_id" value="{{ $settings['google_ads_client_id'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Client Secret</label>
                <input type="password" name="google_ads_client_secret" placeholder="Enter new secret to update" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['google_ads_client_secret'] ?: 'Not set' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Developer Token</label>
                <input type="password" name="google_ads_developer_token" placeholder="Enter new token to update" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['google_ads_developer_token'] ?: 'Not set' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">OAuth Redirect URI</label>
                <input type="text" name="google_ads_redirect_uri" value="{{ $settings['google_ads_redirect_uri'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
    </div>

    <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700">
        <i class="fas fa-save mr-2"></i> Save Ad Platform Settings
    </button>
</form>
@endsection
