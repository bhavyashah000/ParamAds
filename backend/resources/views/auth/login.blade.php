@extends('layouts.app')
@section('title', 'Login')

@section('body')
<div class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
        <div class="text-center mb-8">
            <i class="fas fa-chart-line text-4xl text-primary mb-3"></i>
            <h1 class="text-2xl font-bold text-gray-800">Welcome Back</h1>
            <p class="text-gray-500 text-sm">Sign in to your ParamAds account</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary" required autofocus>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" class="rounded"> Remember me
                    </label>
                </div>
            </div>
            <button type="submit" class="mt-6 w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account? <a href="{{ route('register') }}" class="text-primary hover:underline">Sign up</a>
        </p>
    </div>
</div>
@endsection
