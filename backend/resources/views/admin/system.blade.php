@extends('layouts.admin')
@section('page-title', 'System Settings')

@section('content')
<!-- System Info -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">System Information</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($systemInfo as $label => $value)
        <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $label) }}</p>
            <p class="text-sm font-semibold text-gray-700">{{ $value }}</p>
        </div>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('admin.system.update') }}">
    @csrf @method('PUT')

    <!-- Application -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Application Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Application Name</label>
                <input type="text" name="app_name" value="{{ $settings['app_name'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Application URL</label>
                <input type="url" name="app_url" value="{{ $settings['app_url'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">AI Service URL</label>
                <input type="text" name="ai_service_url" value="{{ $settings['ai_service_url'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" name="app_debug" value="1" {{ $settings['app_debug'] ? 'checked' : '' }} class="rounded">
                <label class="text-sm text-gray-700">Debug Mode (disable in production)</label>
            </div>
        </div>
    </div>

    <!-- Mail -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Email Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Mailer</label>
                <select name="mail_mailer" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    @foreach(['smtp','sendmail','mailgun','ses','log'] as $m)
                        <option value="{{ $m }}" {{ $settings['mail_mailer'] === $m ? 'selected' : '' }}>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Host</label>
                <input type="text" name="mail_host" value="{{ $settings['mail_host'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Port</label>
                <input type="number" name="mail_port" value="{{ $settings['mail_port'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Username</label>
                <input type="text" name="mail_username" value="{{ $settings['mail_username'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                <input type="password" name="mail_password" placeholder="Enter to update" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">From Address</label>
                <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
    </div>

    <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700">
        <i class="fas fa-save mr-2"></i> Save System Settings
    </button>
</form>
@endsection
