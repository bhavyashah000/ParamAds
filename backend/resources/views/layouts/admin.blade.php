@extends('layouts.app')

@section('body')
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-sidebar text-white fixed h-full overflow-y-auto z-30">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-chart-line text-primary"></i> ParamAds
            </h1>
            <p class="text-xs text-gray-400 mt-1">Admin Panel</p>
        </div>
        <nav class="mt-4">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt w-5"></i> Dashboard
            </a>
            <a href="{{ route('admin.users') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <i class="fas fa-users w-5"></i> Users
            </a>
            <a href="{{ route('admin.organizations') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.organizations*') ? 'active' : '' }}">
                <i class="fas fa-building w-5"></i> Organizations
            </a>
            <a href="{{ route('admin.api-settings') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.api-settings*') ? 'active' : '' }}">
                <i class="fas fa-key w-5"></i> API Settings
            </a>
            <a href="{{ route('admin.billing-settings') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.billing-settings*') ? 'active' : '' }}">
                <i class="fas fa-credit-card w-5"></i> Billing Settings
            </a>
            <a href="{{ route('admin.ad-platforms') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.ad-platforms*') ? 'active' : '' }}">
                <i class="fas fa-plug w-5"></i> Ad Platforms
            </a>
            <a href="{{ route('admin.system') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.system*') ? 'active' : '' }}">
                <i class="fas fa-cog w-5"></i> System Settings
            </a>
            <a href="{{ route('admin.logs') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-700 transition {{ request()->routeIs('admin.logs*') ? 'active' : '' }}">
                <i class="fas fa-file-alt w-5"></i> Logs
            </a>
        </nav>
        <div class="absolute bottom-0 w-full p-4 border-t border-gray-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-sm text-gray-400 hover:text-white w-full">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 flex-1">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b px-8 py-4 flex justify-between items-center sticky top-0 z-20">
            <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ auth()->user()->name ?? 'Admin' }}</span>
                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
            </div>
        </header>

        <div class="p-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
@endsection
