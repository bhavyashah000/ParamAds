@extends('layouts.dashboard')
@section('page-title', 'Ads')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">All Ads</h3>
        <a href="{{ route('ads.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
            <i class="fas fa-plus mr-1"></i> Create New Ad
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-3">Ad Name</th>
                    <th class="pb-3">Campaign</th>
                    <th class="pb-3">Platform</th>
                    <th class="pb-3">Type</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3">Created</th>
                    <th class="pb-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ads as $ad)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 font-medium">{{ $ad->name }}</td>
                    <td class="py-3 text-gray-500">{{ $ad->adSet->campaign->name ?? '-' }}</td>
                    <td class="py-3">
                        @if($ad->adSet->campaign->adAccount ?? null)
                            <span class="px-2 py-1 text-xs rounded-full {{ $ad->adSet->campaign->adAccount->platform === 'meta' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($ad->adSet->campaign->adAccount->platform) }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3"><span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 capitalize">{{ $ad->type }}</span></td>
                    <td class="py-3">
                        <span class="px-2 py-1 text-xs rounded-full {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' : ($ad->status === 'draft' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($ad->status) }}
                        </span>
                    </td>
                    <td class="py-3 text-gray-500">{{ $ad->created_at->format('M d, Y') }}</td>
                    <td class="py-3">
                        <a href="#" class="text-primary hover:text-indigo-700"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-400">
                        <i class="fas fa-ad text-5xl mb-4 block"></i>
                        <p class="text-lg">No ads created yet.</p>
                        <a href="{{ route('ads.create') }}" class="text-primary hover:underline mt-2 inline-block">Create your first ad</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $ads->links() }}</div>
</div>
@endsection
