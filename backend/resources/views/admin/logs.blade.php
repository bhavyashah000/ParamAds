@extends('layouts.admin')
@section('page-title', 'System Logs')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">Application Logs</h3>
        <form method="POST" action="{{ route('admin.logs.clear') }}">
            @csrf
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600" onclick="return confirm('Clear all logs?')">
                <i class="fas fa-trash mr-1"></i> Clear Logs
            </button>
        </form>
    </div>
    <div class="bg-gray-900 text-green-400 rounded-lg p-4 overflow-auto max-h-[600px] font-mono text-xs">
        <pre>{{ $logs ?: 'No logs available.' }}</pre>
    </div>
</div>
@endsection
