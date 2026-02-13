@extends('layouts.app')
@section('title', 'Install - Database Setup')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                <div class="w-16 h-1 bg-primary"></div>
                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">2</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">3</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">4</div>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Application & Database Setup</h1>
        <p class="text-gray-500 mb-6">Configure your application URL and database connection.</p>

        <form method="POST" action="{{ route('installer.save-environment') }}">
            @csrf

            <div class="space-y-4">
                <h3 class="text-md font-semibold text-gray-700 border-b pb-2">Application</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Name</label>
                    <input type="text" name="app_name" value="{{ old('app_name', 'ParamAds') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application URL</label>
                    <input type="url" name="app_url" value="{{ old('app_url', 'https://') }}" placeholder="https://yourdomain.com" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    <p class="text-xs text-gray-400 mt-1">Your domain without trailing slash. For CyberPanel, use the domain you configured.</p>
                </div>

                <h3 class="text-md font-semibold text-gray-700 border-b pb-2 mt-6">Database (MySQL)</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                        <input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Port</label>
                        <input type="number" name="db_port" value="{{ old('db_port', '3306') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                    <input type="text" name="db_database" value="{{ old('db_database', 'paramads') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    <p class="text-xs text-gray-400 mt-1">Create this database in CyberPanel first (Databases > Create Database).</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Username</label>
                        <input type="text" name="db_username" value="{{ old('db_username') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
                        <input type="password" name="db_password" value="{{ old('db_password') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </div>

            <button type="submit" class="mt-6 w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Test Connection & Continue <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </form>
    </div>
</div>
@endsection
