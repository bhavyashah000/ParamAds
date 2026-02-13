@extends('layouts.dashboard')
@section('page-title', 'Campaigns')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <!-- Filters -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search campaigns..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm flex-1 min-w-[200px]">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        </select>
        <select name="platform" class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
            <option value="">All Platforms</option>
            <option value="meta" {{ request('platform') === 'meta' ? 'selected' : '' }}>Meta</option>
            <option value="google" {{ request('platform') === 'google' ? 'selected' : '' }}>Google</option>
        </select>
        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('ads.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700"><i class="fas fa-plus mr-1"></i> Create Ad</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-3">Campaign</th>
                    <th class="pb-3">Platform</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3 text-right">Budget</th>
                    <th class="pb-3 text-right">Spend</th>
                    <th class="pb-3 text-right">Revenue</th>
                    <th class="pb-3 text-right">ROAS</th>
                    <th class="pb-3 text-right">Impressions</th>
                    <th class="pb-3 text-right">Clicks</th>
                    <th class="pb-3 text-right">Conv.</th>
                    <th class="pb-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 font-medium max-w-[200px] truncate">{{ $campaign->name }}</td>
                    <td class="py-3">
                        @if($campaign->adAccount)
                            <span class="px-2 py-1 text-xs rounded-full {{ $campaign->adAccount->platform === 'meta' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($campaign->adAccount->platform) }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3">
                        <span class="px-2 py-1 text-xs rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-700' : ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </td>
                    <td class="py-3 text-right">${{ number_format($campaign->daily_budget ?? 0, 2) }}</td>
                    <td class="py-3 text-right">${{ number_format($campaign->total_spend ?? 0, 2) }}</td>
                    <td class="py-3 text-right">${{ number_format($campaign->total_revenue ?? 0, 2) }}</td>
                    <td class="py-3 text-right">
                        @php $roas = ($campaign->total_spend ?? 0) > 0 ? round(($campaign->total_revenue ?? 0) / $campaign->total_spend, 2) : 0; @endphp
                        <span class="{{ $roas >= 2 ? 'text-green-600' : ($roas >= 1 ? 'text-yellow-600' : 'text-red-600') }} font-semibold">{{ $roas }}x</span>
                    </td>
                    <td class="py-3 text-right">{{ number_format($campaign->total_impressions ?? 0) }}</td>
                    <td class="py-3 text-right">{{ number_format($campaign->total_clicks ?? 0) }}</td>
                    <td class="py-3 text-right">{{ number_format($campaign->total_conversions ?? 0) }}</td>
                    <td class="py-3">
                        <div class="flex gap-1">
                            <a href="#" class="text-primary hover:text-indigo-700" title="View"><i class="fas fa-eye"></i></a>
                            <a href="#" class="text-gray-400 hover:text-gray-600" title="Edit"><i class="fas fa-edit"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="py-12 text-center text-gray-400">
                        <i class="fas fa-bullhorn text-4xl mb-3 block"></i>
                        No campaigns found. <a href="{{ route('ads.create') }}" class="text-primary hover:underline">Create your first ad</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $campaigns->links() }}</div>
</div>
@endsection
