@extends('layouts.admin')
@section('page-title', 'Billing Settings')

@section('content')
<form method="POST" action="{{ route('admin.billing-settings.update') }}">
    @csrf @method('PUT')
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fab fa-stripe text-purple-600 text-2xl"></i> Stripe Configuration
        </h3>
        <p class="text-sm text-gray-500 mb-4">Configure Stripe for subscription billing. Create plans in your <a href="https://dashboard.stripe.com/products" target="_blank" class="text-primary hover:underline">Stripe Dashboard</a> first.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Publishable Key</label>
                <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Secret Key</label>
                <input type="password" name="stripe_secret" placeholder="Enter new key to update" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $settings['stripe_secret'] ?: 'Not set' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">Webhook Secret</label>
                <input type="password" name="stripe_webhook_secret" placeholder="whsec_..." class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-400 mt-1">Webhook URL: <code>{{ config('app.url') }}/api/billing/webhook</code></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Subscription Plans</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach(['starter' => ['$49/mo', 'Up to 5 ad accounts'], 'professional' => ['$149/mo', 'Up to 25 ad accounts'], 'enterprise' => ['$499/mo', 'Unlimited ad accounts']] as $plan => $details)
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 capitalize">{{ $plan }}</h4>
                <p class="text-2xl font-bold text-primary mt-1">{{ $details[0] }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $details[1] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700">
        <i class="fas fa-save mr-2"></i> Save Billing Settings
    </button>
</form>
@endsection
