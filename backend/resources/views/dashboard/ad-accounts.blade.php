@extends('layouts.dashboard')
@section('page-title', 'Ad Accounts')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Connect Ad Platforms</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ url('/api/ad-accounts/meta/connect') }}" class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-facebook text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-700">Meta Ads</p>
                <p class="text-sm text-gray-500">Connect Facebook & Instagram Ads</p>
            </div>
        </a>
        <a href="{{ url('/api/ad-accounts/google/connect') }}" class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:border-red-300 hover:bg-red-50 transition">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-google text-red-500 text-2xl"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-700">Google Ads</p>
                <p class="text-sm text-gray-500">Connect Google Ads account</p>
            </div>
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Connected Accounts</h3>
    @if($accounts->count())
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-3">Platform</th>
                    <th class="pb-3">Account Name</th>
                    <th class="pb-3">Account ID</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3">Connected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3">
                        <span class="px-2 py-1 text-xs rounded-full {{ $account->platform === 'meta' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($account->platform) }}
                        </span>
                    </td>
                    <td class="py-3 font-medium">{{ $account->account_name }}</td>
                    <td class="py-3 text-gray-500 font-mono text-xs">{{ $account->platform_account_id }}</td>
                    <td class="py-3">
                        <span class="px-2 py-1 text-xs rounded-full {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="py-3 text-gray-500">{{ $account->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="p-8 text-center text-gray-400">
            <i class="fas fa-plug text-4xl mb-3"></i>
            <p>No ad accounts connected yet. Use the buttons above to connect.</p>
        </div>
    @endif
</div>
@endsection
