@extends('layouts.dashboard')
@section('page-title', 'Creative Intelligence')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">Creative Performance</h3>
    </div>
    <div class="p-12 text-center text-gray-400">
        <i class="fas fa-image text-5xl mb-4"></i>
        <p class="text-lg">No creatives analyzed yet.</p>
        <p class="text-sm mt-1">Sync your campaigns to analyze creative performance, fatigue, and trends.</p>
    </div>
</div>
@endsection
