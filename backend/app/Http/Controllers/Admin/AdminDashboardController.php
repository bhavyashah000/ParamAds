<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Metrics\Models\CampaignMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_organizations' => Organization::count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'total_spend' => CampaignMetric::where('date', '>=', now()->subDays(30))->sum('spend'),
            'total_revenue' => CampaignMetric::where('date', '>=', now()->subDays(30))->sum('revenue'),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];

        $recentUsers = User::orderByDesc('created_at')->take(10)->get();

        $dailyMetrics = CampaignMetric::where('date', '>=', now()->subDays(30))
            ->select('date', DB::raw('SUM(spend) as spend'), DB::raw('SUM(revenue) as revenue'), DB::raw('SUM(conversions) as conversions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'dailyMetrics'));
    }
}
