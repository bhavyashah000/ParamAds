<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Metrics\Models\CampaignMetric;
use App\Modules\AdAccounts\Models\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index()
    {
        $orgId = auth()->user()->organization_id;

        $stats = [
            'total_campaigns' => Campaign::where('organization_id', $orgId)->count(),
            'active_campaigns' => Campaign::where('organization_id', $orgId)->where('status', 'active')->count(),
            'total_spend_30d' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(30))->sum('spend'),
            'total_revenue_30d' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(30))->sum('revenue'),
            'total_conversions_30d' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(30))->sum('conversions'),
            'total_impressions_30d' => CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
                ->where('date', '>=', now()->subDays(30))->sum('impressions'),
            'ad_accounts' => AdAccount::where('organization_id', $orgId)->count(),
        ];

        $roas = $stats['total_spend_30d'] > 0 ? round($stats['total_revenue_30d'] / $stats['total_spend_30d'], 2) : 0;
        $stats['roas'] = $roas;

        $dailyMetrics = CampaignMetric::whereHas('campaign', fn($q) => $q->where('organization_id', $orgId))
            ->where('date', '>=', now()->subDays(30))
            ->select('date', DB::raw('SUM(spend) as spend'), DB::raw('SUM(revenue) as revenue'), DB::raw('SUM(conversions) as conversions'), DB::raw('SUM(clicks) as clicks'), DB::raw('SUM(impressions) as impressions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topCampaigns = Campaign::where('organization_id', $orgId)
            ->withSum(['metrics as total_spend' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'spend')
            ->withSum(['metrics as total_revenue' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'revenue')
            ->withSum(['metrics as total_conversions' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'conversions')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        return view('dashboard.index', compact('stats', 'dailyMetrics', 'topCampaigns'));
    }

    public function campaigns(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $campaigns = Campaign::where('organization_id', $orgId)
            ->with('adAccount')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->platform, fn($q, $p) => $q->whereHas('adAccount', fn($q2) => $q2->where('platform', $p)))
            ->withSum(['metrics as total_spend' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'spend')
            ->withSum(['metrics as total_revenue' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'revenue')
            ->withSum(['metrics as total_clicks' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'clicks')
            ->withSum(['metrics as total_impressions' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'impressions')
            ->withSum(['metrics as total_conversions' => fn($q) => $q->where('date', '>=', now()->subDays(30))], 'conversions')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('dashboard.campaigns', compact('campaigns'));
    }

    public function analytics()
    {
        return view('dashboard.analytics');
    }

    public function audiences()
    {
        return view('dashboard.audiences');
    }

    public function creatives()
    {
        return view('dashboard.creatives');
    }

    public function automation()
    {
        return view('dashboard.automation');
    }

    public function adAccounts()
    {
        $orgId = auth()->user()->organization_id;
        $accounts = AdAccount::where('organization_id', $orgId)->get();
        return view('dashboard.ad-accounts', compact('accounts'));
    }

    public function reports()
    {
        return view('dashboard.reports');
    }

    public function settings()
    {
        return view('dashboard.settings');
    }
}
