@extends('layouts.app')
@section('title', 'Install ParamAds')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">1</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">2</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">3</div>
                <div class="w-16 h-1 bg-gray-200"></div>
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm">4</div>
            </div>
        </div>

        <div class="text-center mb-8">
            <i class="fas fa-chart-line text-5xl text-primary mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Welcome to ParamAds</h1>
            <p class="text-gray-500 mt-2">AI-Powered Marketing Intelligence Platform</p>
        </div>

        <h2 class="text-lg font-semibold text-gray-700 mb-4">System Requirements</h2>

        <div class="space-y-2 mb-6">
            @php $allPassed = true; @endphp
            @foreach($requirements as $name => $passed)
                @if(!$passed) @php $allPassed = false; @endphp @endif
                <div class="flex items-center justify-between p-3 rounded-lg {{ $passed ? 'bg-green-50' : 'bg-red-50' }}">
                    <span class="text-sm {{ $passed ? 'text-green-700' : 'text-red-700' }}">{{ $name }}</span>
                    @if($passed)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @else
                        <i class="fas fa-times-circle text-red-500"></i>
                    @endif
                </div>
            @endforeach
        </div>

        @if($allPassed)
            <a href="{{ route('installer.environment') }}" class="block w-full text-center bg-primary text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Continue to Database Setup <i class="fas fa-arrow-right ml-2"></i>
            </a>
        @else
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Please fix the above requirements before continuing. If you are on CyberPanel, ensure all PHP extensions are enabled.
            </div>
        @endif
    </div>
</div>
@endsection
