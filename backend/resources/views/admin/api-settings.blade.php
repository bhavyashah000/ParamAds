@extends('layouts.admin')
@section('page-title', 'API Settings')

@section('content')
<form method="POST" action="{{ route('admin.api-settings.update') }}">
    @csrf @method('PUT')

    <!-- Stripe -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-stripe text-purple-600 text-2xl"></i> Stripe (Billing & Payments)
        </h3>
        <p class="text-sm text-gray-500 mb-4">Required for subscription billing. Get your keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-primary hover:underline">Stripe Dashboard</a>.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Publishable Key</label>
                <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] }}" placeholder="pk_live_..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Secret Key</label>
                <input type="password" name="stripe_secret" placeholder="Enter new secret key to update" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['stripe_secret'] ?: 'Not set' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">Webhook Secret</label>
                <input type="password" name="stripe_webhook_secret" placeholder="whsec_..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['stripe_webhook_secret'] ?: 'Not set' }}</p>
            </div>
        </div>
    </div>

    <!-- Meta Ads -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-facebook text-blue-600 text-2xl"></i> Meta (Facebook) Ads
        </h3>
        <p class="text-sm text-gray-500 mb-4">Required for Meta Ads integration. Get your credentials from <a href="https://developers.facebook.com" target="_blank" class="text-primary hover:underline">Meta for Developers</a>.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">App ID</label>
                <input type="text" name="meta_app_id" value="{{ $settings['meta_app_id'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">App Secret</label>
                <input type="password" name="meta_app_secret" placeholder="Enter new secret to update" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['meta_app_secret'] ?: 'Not set' }}</p>
            </div>
        </div>
    </div>

    <!-- Google Ads -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-google text-red-500 text-2xl"></i> Google Ads
        </h3>
        <p class="text-sm text-gray-500 mb-4">Required for Google Ads integration. Get your credentials from <a href="https://console.cloud.google.com" target="_blank" class="text-primary hover:underline">Google Cloud Console</a>.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Client ID</label>
                <input type="text" name="google_ads_client_id" value="{{ $settings['google_ads_client_id'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Client Secret</label>
                <input type="password" name="google_ads_client_secret" placeholder="Enter new secret to update" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['google_ads_client_secret'] ?: 'Not set' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">Developer Token</label>
                <input type="password" name="google_ads_developer_token" placeholder="Enter new token to update" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['google_ads_developer_token'] ?: 'Not set' }}</p>
            </div>
        </div>
    </div>

    <!-- OpenAI -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fas fa-brain text-green-600 text-2xl"></i> OpenAI (AI Features)
        </h3>
        <p class="text-sm text-gray-500 mb-4">Required for AI-powered insights, forecasting, and NL Q&A. Get your key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary hover:underline">OpenAI Platform</a>.</p>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">API Key</label>
            <input type="password" name="openai_api_key" placeholder="Enter new key to update" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
            <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['openai_api_key'] ?: 'Not set' }}</p>
        </div>
    </div>

    <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
        <i class="fas fa-save mr-2"></i> Save All API Settings
    </button>
</form>
@endsection
