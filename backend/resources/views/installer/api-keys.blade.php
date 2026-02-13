@extends('layouts.app')
@section('title', 'Install - API Configuration')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-8 max-h-[90vh] overflow-y-auto">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-green-500"></div>
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-primary"></div>
                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">3</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">4</div>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">API & Service Configuration</h1>
        <p class="text-gray-500 mb-6">Configure your external service API keys. You can skip any and add them later from the Admin Panel.</p>

        <form method="POST" action="{{ route('installer.save-api-keys') }}">
            @csrf

            <!-- Stripe -->
            <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-3">
                    <i class="fab fa-stripe text-purple-600 text-xl"></i> Stripe (Billing)
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Publishable Key</label>
                        <input type="text" name="stripe_key" placeholder="pk_live_..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Secret Key</label>
                        <input type="password" name="stripe_secret" placeholder="sk_live_..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Meta Ads -->
            <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-3">
                    <i class="fab fa-facebook text-blue-600 text-xl"></i> Meta (Facebook) Ads
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">App ID</label>
                        <input type="text" name="meta_app_id" placeholder="Your Meta App ID" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">App Secret</label>
                        <input type="password" name="meta_app_secret" placeholder="Your Meta App Secret" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Google Ads -->
            <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-3">
                    <i class="fab fa-google text-red-500 text-xl"></i> Google Ads
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Client ID</label>
                        <input type="text" name="google_ads_client_id" placeholder="OAuth Client ID" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Client Secret</label>
                        <input type="password" name="google_ads_client_secret" placeholder="OAuth Client Secret" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Developer Token</label>
                    <input type="text" name="google_ads_developer_token" placeholder="Developer Token" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <!-- OpenAI -->
            <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-3">
                    <i class="fas fa-brain text-green-600 text-xl"></i> OpenAI (AI Features)
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">API Key</label>
                    <input type="password" name="openai_api_key" placeholder="sk-..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-400 mt-1">Required for AI-powered insights, forecasting, and NL Q&A.</p>
                </div>
            </div>

            <!-- Mail -->
            <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-3">
                    <i class="fas fa-envelope text-orange-500 text-xl"></i> Email (SMTP)
                </h3>
                <div class="grid grid-cols-3 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Mailer</label>
                        <select name="mail_mailer" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="mailgun">Mailgun</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Host</label>
                        <input type="text" name="mail_host" placeholder="smtp.gmail.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Port</label>
                        <input type="number" name="mail_port" value="587" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Username</label>
                        <input type="text" name="mail_username" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                        <input type="password" name="mail_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">From Address</label>
                        <input type="email" name="mail_from_address" placeholder="noreply@yourdomain.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('installer.environment') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Save & Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
