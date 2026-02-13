@extends('layouts.dashboard')
@section('page-title', 'Audiences')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">Audience Segments</h3>
        <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Create Audience</button>
    </div>
    <div class="p-12 text-center text-gray-400">
        <i class="fas fa-users text-5xl mb-4"></i>
        <p class="text-lg">No audiences configured yet.</p>
        <p class="text-sm mt-1">Connect your pixels and sync audiences from your ad platforms.</p>
    </div>
</div>
@endsection
