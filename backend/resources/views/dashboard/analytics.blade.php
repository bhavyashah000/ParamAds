@extends('layouts.dashboard')
@section('page-title', 'Analytics')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Performance Overview</h3>
        <canvas id="performanceChart" height="250"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Platform Comparison</h3>
        <canvas id="platformChart" height="250"></canvas>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">AI Insights</h3>
    <div class="p-8 text-center text-gray-400">
        <i class="fas fa-brain text-4xl mb-3"></i>
        <p>Connect your ad accounts and sync data to unlock AI-powered analytics.</p>
    </div>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('performanceChart'), {
    type: 'line',
    data: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], datasets: [{ label: 'ROAS', data: [2.1,2.4,1.8,3.1,2.8,2.5,3.0], borderColor: '#4F46E5', tension: 0.4 }] },
    options: { responsive: true }
});
new Chart(document.getElementById('platformChart'), {
    type: 'doughnut',
    data: { labels: ['Meta Ads','Google Ads'], datasets: [{ data: [60,40], backgroundColor: ['#3B82F6','#EF4444'] }] },
    options: { responsive: true }
});
</script>
@endpush
@endsection
