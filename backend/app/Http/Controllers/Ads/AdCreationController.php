<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\AdSet;
use App\Modules\Campaigns\Models\Ad;
use App\Modules\AdAccounts\Models\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdCreationController extends Controller
{
    /**
     * Show ads listing.
     */
    public function index(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $ads = Ad::whereHas('adSet.campaign', fn($q) => $q->where('organization_id', $orgId))
            ->with(['adSet.campaign.adAccount'])
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('ads.index', compact('ads'));
    }

    /**
     * Show ad creation form.
     */
    public function create()
    {
        $orgId = auth()->user()->organization_id;
        $adAccounts = AdAccount::where('organization_id', $orgId)->where('is_active', true)->get();

        return view('ads.create', compact('adAccounts'));
    }

    /**
     * Store a new ad with campaign, ad set, and geographic targeting.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ad_account_id' => 'required|exists:ad_accounts,id',
            'campaign_name' => 'required|string|max:255',
            'campaign_objective' => 'required|string|in:conversions,traffic,awareness,engagement,leads,sales',
            'daily_budget' => 'required|numeric|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',

            // Ad Set
            'adset_name' => 'required|string|max:255',
            'age_min' => 'nullable|integer|min:13|max:65',
            'age_max' => 'nullable|integer|min:18|max:65',
            'genders' => 'nullable|array',
            'interests' => 'nullable|string',

            // Geographic Targeting
            'countries' => 'nullable|string',
            'states' => 'nullable|string',
            'cities' => 'nullable|string',
            'zipcodes' => 'nullable|string',
            'pincodes' => 'nullable|string',
            'radius' => 'nullable|numeric|min:1|max:500',
            'radius_unit' => 'nullable|in:km,mi',
            'location_type' => 'nullable|in:home,recent,travel',

            // Ad Creative
            'ad_name' => 'required|string|max:255',
            'ad_type' => 'required|in:image,video,carousel,text',
            'headline' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'primary_text' => 'nullable|string|max:2000',
            'call_to_action' => 'required|string',
            'destination_url' => 'required|url',
            'display_url' => 'nullable|string|max:255',
            'creative_media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,webm|max:51200',
        ]);

        $orgId = auth()->user()->organization_id;

        DB::beginTransaction();
        try {
            // Build geo targeting data
            $geoTargeting = $this->buildGeoTargeting($request);

            // Create Campaign
            $campaign = Campaign::create([
                'organization_id' => $orgId,
                'ad_account_id' => $request->ad_account_id,
                'name' => $request->campaign_name,
                'objective' => $request->campaign_objective,
                'status' => 'draft',
                'daily_budget' => $request->daily_budget,
                'lifetime_budget' => $request->lifetime_budget,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'settings' => [
                    'geo_targeting' => $geoTargeting,
                ],
            ]);

            // Create Ad Set
            $targeting = [
                'age_min' => $request->age_min ?? 18,
                'age_max' => $request->age_max ?? 65,
                'genders' => $request->genders ?? [],
                'interests' => $request->interests ? array_map('trim', explode(',', $request->interests)) : [],
                'geo_targeting' => $geoTargeting,
            ];

            $adSet = AdSet::create([
                'campaign_id' => $campaign->id,
                'name' => $request->adset_name,
                'status' => 'draft',
                'targeting' => $targeting,
                'daily_budget' => $request->daily_budget,
            ]);

            // Handle media upload
            $mediaUrl = null;
            if ($request->hasFile('creative_media')) {
                $mediaUrl = $request->file('creative_media')->store('ad-creatives', 'public');
            }

            // Create Ad
            $ad = Ad::create([
                'ad_set_id' => $adSet->id,
                'name' => $request->ad_name,
                'type' => $request->ad_type,
                'status' => 'draft',
                'creative' => [
                    'headline' => $request->headline,
                    'description' => $request->description,
                    'primary_text' => $request->primary_text,
                    'call_to_action' => $request->call_to_action,
                    'destination_url' => $request->destination_url,
                    'display_url' => $request->display_url,
                    'media_url' => $mediaUrl,
                ],
            ]);

            DB::commit();

            return redirect()->route('ads.index')->with('success', 'Ad created successfully! Campaign: ' . $campaign->name);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['create' => 'Failed to create ad: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Build geographic targeting array from request.
     */
    private function buildGeoTargeting(Request $request): array
    {
        $geo = [];

        if ($request->filled('countries')) {
            $geo['countries'] = array_map('trim', explode(',', $request->countries));
        }
        if ($request->filled('states')) {
            $geo['states'] = array_map('trim', explode(',', $request->states));
        }
        if ($request->filled('cities')) {
            $geo['cities'] = array_map('trim', explode(',', $request->cities));
        }
        if ($request->filled('zipcodes')) {
            $geo['zipcodes'] = array_map('trim', explode(',', $request->zipcodes));
        }
        if ($request->filled('pincodes')) {
            $geo['pincodes'] = array_map('trim', explode(',', $request->pincodes));
        }
        if ($request->filled('radius')) {
            $geo['radius'] = [
                'value' => (float) $request->radius,
                'unit' => $request->radius_unit ?? 'km',
            ];
        }
        if ($request->filled('location_type')) {
            $geo['location_type'] = $request->location_type;
        }

        return $geo;
    }
}
