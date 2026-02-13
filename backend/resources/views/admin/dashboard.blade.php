@extends('layouts.admin')
@section('page-title', 'Admin Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs text-green-500 mt-1">+{{ $stats['new_users_week'] }} this week</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Organizations</p>
                <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_organizations']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-building text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Active Campaigns</p>
                <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['active_campaigns']) }}</p>
                <p class="text-xs text-gray-400 mt-1">of {{ $stats['total_campaigns'] }} total</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-bullhorn text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue (30d)</p>
                <p class="text-3xl font-bold text-gray-800">${{ number_format($stats['total_revenue'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">Spend: ${{ number_format($stats['total_spend'], 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-dollar-sign text-yellow-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Revenue vs Spend (30 Days)</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Conversions (30 Days)</h3>
        <canvas id="conversionsChart" height="200"></canvas>
    </div>
</div>

<!-- Recent Users -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Users</h3>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3">Name</th>
                <th class="pb-3">Email</th>
                <th class="pb-3">Role</th>
                <th class="pb-3">Joined</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentUsers as $user)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3">{{ $user->name }}</td>
                <td class="py-3 text-gray-500">{{ $user->email }}</td>
                <td class="py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $user->role }}</span></td>
                <td class="py-3 text-gray-500">{{ $user->created_at->diffForHumans() }}</td>
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
            { label: 'Revenue', data: dailyData.map(d => d.revenue), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
            { label: 'Spend', data: dailyData.map(d => d.spend), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { display: false } } }
});

new Chart(document.getElementById('conversionsChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{ label: 'Conversions', data: dailyData.map(d => d.conversions), backgroundColor: '#6366F1' }]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { display: false } } }
});
</script>
@endpush
@endsection
