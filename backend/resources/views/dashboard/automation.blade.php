@extends('layouts.dashboard')
@section('page-title', 'Automation Rules')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-700">Automation Rules</h3>
        <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Create Rule</button>
    </div>
    <div class="p-12 text-center text-gray-400">
        <i class="fas fa-robot text-5xl mb-4"></i>
        <p class="text-lg">No automation rules configured.</p>
        <p class="text-sm mt-1">Create rules like "If ROAS drops below 1.5, pause campaign" to automate your ad management.</p>
    </div>
</div>
@endsection
