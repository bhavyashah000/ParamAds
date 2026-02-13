@extends('layouts.app')

@section('body')
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-dark text-white fixed h-full overflow-y-auto z-30">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-chart-line text-accent"></i> ParamAds
            </h1>
            <p class="text-xs text-gray-400 mt-1">{{ auth()->user()->organization->name ?? 'My Workspace' }}</p>
        </div>
        <nav class="mt-4">
            <p class="px-6 py-2 text-xs text-gray-500 uppercase tracking-wider">Main</p>
            <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt w-5"></i> Dashboard
            </a>
            <a href="{{ route('campaigns.index') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('campaigns*') ? 'active' : '' }}">
                <i class="fas fa-bullhorn w-5"></i> Campaigns
            </a>
            <a href="{{ route('ads.index') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('ads*') ? 'active' : '' }}">
                <i class="fas fa-ad w-5"></i> Ads
            </a>
            <a href="{{ route('ads.create') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('ads.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle w-5"></i> Create Ad
            </a>

            <p class="px-6 py-2 mt-4 text-xs text-gray-500 uppercase tracking-wider">Intelligence</p>
            <a href="{{ route('analytics') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('analytics*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar w-5"></i> Analytics
            </a>
            <a href="{{ route('audiences') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('audiences*') ? 'active' : '' }}">
                <i class="fas fa-users w-5"></i> Audiences
            </a>
            <a href="{{ route('creatives') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('creatives*') ? 'active' : '' }}">
                <i class="fas fa-image w-5"></i> Creatives
            </a>
            <a href="{{ route('automation') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('automation*') ? 'active' : '' }}">
                <i class="fas fa-robot w-5"></i> Automation
            </a>

            <p class="px-6 py-2 mt-4 text-xs text-gray-500 uppercase tracking-wider">Account</p>
            <a href="{{ route('ad-accounts') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('ad-accounts*') ? 'active' : '' }}">
                <i class="fas fa-plug w-5"></i> Ad Accounts
            </a>
            <a href="{{ route('reports') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('reports*') ? 'active' : '' }}">
                <i class="fas fa-file-chart-line w-5"></i> Reports
            </a>
            <a href="{{ route('settings') }}" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 transition {{ request()->routeIs('settings*') ? 'active' : '' }}">
                <i class="fas fa-cog w-5"></i> Settings
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 flex-1">
        <header class="bg-white shadow-sm border-b px-8 py-4 flex justify-between items-center sticky top-0 z-20">
            <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
            <div class="flex items-center gap-4">
                <button class="text-gray-400 hover:text-gray-600"><i class="fas fa-bell"></i></button>
                <span class="text-sm text-gray-500">{{ auth()->user()->name ?? 'User' }}</span>
                <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button class="text-gray-400 hover:text-red-500 text-sm"><i class="fas fa-sign-out-alt"></i></button>
                </form>
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
            @yield('content')
        </div>
    </main>
</div>
@endsection
