@extends('layouts.dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Spend (30d)</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($stats['total_spend_30d'], 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-dollar-sign text-red-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Revenue (30d)</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($stats['total_revenue_30d'], 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">ROAS</p>
                <p class="text-2xl font-bold {{ $stats['roas'] >= 2 ? 'text-green-600' : ($stats['roas'] >= 1 ? 'text-yellow-600' : 'text-red-600') }}">{{ $stats['roas'] }}x</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-percentage text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Conversions (30d)</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_conversions_30d']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-bullseye text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-bullhorn text-indigo-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Active Campaigns</p>
                <p class="text-xl font-bold">{{ $stats['active_campaigns'] }} <span class="text-sm font-normal text-gray-400">/ {{ $stats['total_campaigns'] }}</span></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-eye text-cyan-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Impressions (30d)</p>
                <p class="text-xl font-bold">{{ number_format($stats['total_impressions_30d']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-plug text-orange-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Ad Accounts</p>
                <p class="text-xl font-bold">{{ $stats['ad_accounts'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Revenue vs Spend</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Clicks & Conversions</h3>
        <canvas id="clicksChart" height="200"></canvas>
    </div>
</div>

<!-- Top Campaigns -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">Top Campaigns (30 Days)</h3>
        <a href="{{ route('campaigns.index') }}" class="text-primary text-sm hover:underline">View All</a>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3">Campaign</th>
                <th class="pb-3">Status</th>
                <th class="pb-3 text-right">Spend</th>
                <th class="pb-3 text-right">Revenue</th>
                <th class="pb-3 text-right">ROAS</th>
                <th class="pb-3 text-right">Conversions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topCampaigns as $campaign)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 font-medium">{{ $campaign->name }}</td>
                <td class="py-3">
                    <span class="px-2 py-1 text-xs rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </td>
                <td class="py-3 text-right">${{ number_format($campaign->total_spend ?? 0, 2) }}</td>
                <td class="py-3 text-right">${{ number_format($campaign->total_revenue ?? 0, 2) }}</td>
                <td class="py-3 text-right">
                    @php $roas = ($campaign->total_spend ?? 0) > 0 ? round(($campaign->total_revenue ?? 0) / $campaign->total_spend, 2) : 0; @endphp
                    <span class="{{ $roas >= 2 ? 'text-green-600' : ($roas >= 1 ? 'text-yellow-600' : 'text-red-600') }} font-semibold">{{ $roas }}x</span>
                </td>
                <td class="py-3 text-right">{{ number_format($campaign->total_conversions ?? 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
const dailyData = @json($dailyMetrics);
const labels = dailyData.map(d => d.date);

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'Revenue', data: dailyData.map(d => parseFloat(d.revenue)), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
            { label: 'Spend', data: dailyData.map(d => parseFloat(d.spend)), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});

new Chart(document.getElementById('clicksChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'Clicks', data: dailyData.map(d => parseInt(d.clicks)), backgroundColor: '#6366F1' },
            { label: 'Conversions', data: dailyData.map(d => parseInt(d.conversions)), backgroundColor: '#F59E0B' }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});
</script>
@endpush
@endsection
