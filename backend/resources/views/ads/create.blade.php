@extends('layouts.dashboard')
@section('page-title', 'Create New Ad')

@section('content')
<form method="POST" action="{{ route('ads.store') }}" enctype="multipart/form-data" class="max-w-4xl">
    @csrf

    <!-- Step 1: Campaign Setup -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">1</div>
            <h3 class="text-lg font-semibold text-gray-700">Campaign Setup</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ad Account <span class="text-red-500">*</span></label>
                <select name="ad_account_id" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="">Select Ad Account</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('ad_account_id') == $account->id ? 'selected' : '' }}>
                            {{ ucfirst($account->platform) }} - {{ $account->account_name }} ({{ $account->platform_account_id }})
                        </option>
                    @endforeach
                </select>
                @if($adAccounts->isEmpty())
                    <p class="text-xs text-red-500 mt-1">No ad accounts connected. <a href="{{ route('ad-accounts') }}" class="underline">Connect one first</a>.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name <span class="text-red-500">*</span></label>
                <input type="text" name="campaign_name" value="{{ old('campaign_name') }}" placeholder="e.g., Summer Sale 2025" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Objective <span class="text-red-500">*</span></label>
                <select name="campaign_objective" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="">Select Objective</option>
                    <option value="conversions" {{ old('campaign_objective') === 'conversions' ? 'selected' : '' }}>Conversions</option>
                    <option value="traffic" {{ old('campaign_objective') === 'traffic' ? 'selected' : '' }}>Traffic</option>
                    <option value="awareness" {{ old('campaign_objective') === 'awareness' ? 'selected' : '' }}>Brand Awareness</option>
                    <option value="engagement" {{ old('campaign_objective') === 'engagement' ? 'selected' : '' }}>Engagement</option>
                    <option value="leads" {{ old('campaign_objective') === 'leads' ? 'selected' : '' }}>Lead Generation</option>
                    <option value="sales" {{ old('campaign_objective') === 'sales' ? 'selected' : '' }}>Sales</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Daily Budget ($) <span class="text-red-500">*</span></label>
                <input type="number" name="daily_budget" value="{{ old('daily_budget') }}" step="0.01" min="1" placeholder="50.00" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lifetime Budget ($)</label>
                <input type="number" name="lifetime_budget" value="{{ old('lifetime_budget') }}" step="0.01" min="0" placeholder="Optional" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
    </div>

    <!-- Step 2: Audience Targeting -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">2</div>
            <h3 class="text-lg font-semibold text-gray-700">Audience Targeting</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ad Set Name <span class="text-red-500">*</span></label>
                <input type="text" name="adset_name" value="{{ old('adset_name') }}" placeholder="e.g., US Males 25-45" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>
            <div></div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Age Range</label>
                <div class="flex gap-2 items-center">
                    <input type="number" name="age_min" value="{{ old('age_min', 18) }}" min="13" max="65" class="w-24 border border-gray-300 rounded-lg px-3 py-2">
                    <span class="text-gray-500">to</span>
                    <input type="number" name="age_max" value="{{ old('age_max', 65) }}" min="18" max="65" class="w-24 border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2"><input type="checkbox" name="genders[]" value="male" class="rounded"> Male</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="genders[]" value="female" class="rounded"> Female</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="genders[]" value="all" class="rounded" checked> All</label>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Interests</label>
                <input type="text" name="interests" value="{{ old('interests') }}" placeholder="e.g., fitness, technology, fashion (comma separated)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>

        <!-- Geographic Targeting -->
        <div class="border-t pt-6">
            <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2 mb-4">
                <i class="fas fa-map-marker-alt text-red-500"></i> Geographic Targeting
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Countries</label>
                    <input type="text" name="countries" value="{{ old('countries') }}" placeholder="e.g., US, UK, IN, CA (comma separated)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <p class="text-xs text-gray-400 mt-1">Use ISO country codes</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">States / Regions</label>
                    <input type="text" name="states" value="{{ old('states') }}" placeholder="e.g., California, New York, Maharashtra" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cities</label>
                    <input type="text" name="cities" value="{{ old('cities') }}" placeholder="e.g., New York, Mumbai, London" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Type</label>
                    <select name="location_type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="home" {{ old('location_type') === 'home' ? 'selected' : '' }}>People living in this location</option>
                        <option value="recent" {{ old('location_type') === 'recent' ? 'selected' : '' }}>People recently in this location</option>
                        <option value="travel" {{ old('location_type') === 'travel' ? 'selected' : '' }}>People traveling to this location</option>
                    </select>
                </div>

                <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h5 class="text-sm font-semibold text-blue-700 mb-3">
                        <i class="fas fa-map-pin mr-1"></i> Pincode / Zipcode Targeting
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">Zip Codes (US/UK/etc.)</label>
                            <textarea name="zipcodes" rows="3" placeholder="Enter zip codes, one per line or comma separated&#10;e.g., 10001, 10002, 90210, SW1A 1AA" class="w-full border border-blue-300 rounded-lg px-4 py-2 text-sm">{{ old('zipcodes') }}</textarea>
                            <p class="text-xs text-blue-500 mt-1">Supports US ZIP codes, UK postcodes, and other formats</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">Pin Codes (India)</label>
                            <textarea name="pincodes" rows="3" placeholder="Enter pin codes, one per line or comma separated&#10;e.g., 110001, 400001, 560001" class="w-full border border-blue-300 rounded-lg px-4 py-2 text-sm">{{ old('pincodes') }}</textarea>
                            <p class="text-xs text-blue-500 mt-1">6-digit Indian PIN codes for precise targeting</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Radius (around locations)</label>
                    <div class="flex gap-2">
                        <input type="number" name="radius" value="{{ old('radius') }}" min="1" max="500" step="1" placeholder="e.g., 25" class="flex-1 border border-gray-300 rounded-lg px-4 py-2">
                        <select name="radius_unit" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="km" {{ old('radius_unit') === 'km' ? 'selected' : '' }}>km</option>
                            <option value="mi" {{ old('radius_unit') === 'mi' ? 'selected' : '' }}>mi</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Ad Creative -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">3</div>
            <h3 class="text-lg font-semibold text-gray-700">Ad Creative</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ad Name <span class="text-red-500">*</span></label>
                <input type="text" name="ad_name" value="{{ old('ad_name') }}" placeholder="e.g., Summer Sale Banner" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ad Type <span class="text-red-500">*</span></label>
                <select name="ad_type" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="image" {{ old('ad_type') === 'image' ? 'selected' : '' }}>Single Image</option>
                    <option value="video" {{ old('ad_type') === 'video' ? 'selected' : '' }}>Video</option>
                    <option value="carousel" {{ old('ad_type') === 'carousel' ? 'selected' : '' }}>Carousel</option>
                    <option value="text" {{ old('ad_type') === 'text' ? 'selected' : '' }}>Text Only</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Headline <span class="text-red-500">*</span></label>
                <input type="text" name="headline" value="{{ old('headline') }}" placeholder="e.g., 50% Off Everything This Weekend!" maxlength="255" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                <p class="text-xs text-gray-400 mt-1"><span id="headlineCount">0</span>/255 characters</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Primary Text</label>
                <textarea name="primary_text" rows="3" maxlength="2000" placeholder="Main ad text that appears above the image/video..." class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old('primary_text') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" maxlength="1000" placeholder="Additional description text..." class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Call to Action <span class="text-red-500">*</span></label>
                <select name="call_to_action" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="LEARN_MORE">Learn More</option>
                    <option value="SHOP_NOW">Shop Now</option>
                    <option value="SIGN_UP">Sign Up</option>
                    <option value="BOOK_NOW">Book Now</option>
                    <option value="CONTACT_US">Contact Us</option>
                    <option value="DOWNLOAD">Download</option>
                    <option value="GET_OFFER">Get Offer</option>
                    <option value="GET_QUOTE">Get Quote</option>
                    <option value="SUBSCRIBE">Subscribe</option>
                    <option value="APPLY_NOW">Apply Now</option>
                    <option value="BUY_NOW">Buy Now</option>
                    <option value="WATCH_MORE">Watch More</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Destination URL <span class="text-red-500">*</span></label>
                <input type="url" name="destination_url" value="{{ old('destination_url') }}" placeholder="https://yourwebsite.com/landing" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display URL</label>
                <input type="text" name="display_url" value="{{ old('display_url') }}" placeholder="yourwebsite.com" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Creative Media</label>
                <input type="file" name="creative_media" accept="image/*,video/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, MP4, WebM. Max 50MB.</p>
            </div>
        </div>
    </div>

    <!-- Ad Preview -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4"><i class="fas fa-eye mr-2"></i>Ad Preview</h3>
        <div class="max-w-sm mx-auto border border-gray-200 rounded-lg overflow-hidden">
            <div class="p-3 flex items-center gap-2 border-b">
                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                <div>
                    <p class="text-sm font-semibold" id="previewBrand">Your Brand</p>
                    <p class="text-xs text-gray-400">Sponsored</p>
                </div>
            </div>
            <div class="p-3">
                <p class="text-sm" id="previewPrimaryText">Your primary text will appear here...</p>
            </div>
            <div class="bg-gray-100 h-48 flex items-center justify-center text-gray-400">
                <i class="fas fa-image text-4xl"></i>
            </div>
            <div class="p-3 border-t">
                <p class="text-xs text-gray-400" id="previewDisplayUrl">yourwebsite.com</p>
                <p class="text-sm font-semibold" id="previewHeadline">Your headline here</p>
                <p class="text-xs text-gray-500" id="previewDescription">Your description here</p>
            </div>
            <div class="p-3 border-t">
                <button class="w-full bg-primary text-white py-2 rounded text-sm font-semibold" id="previewCTA">Learn More</button>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-4">
        <a href="{{ route('ads.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancel</a>
        <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
            <i class="fas fa-rocket mr-2"></i> Create Ad
        </button>
    </div>
</form>

@push('scripts')
<script>
// Live preview
document.querySelector('[name="headline"]').addEventListener('input', function() {
    document.getElementById('previewHeadline').textContent = this.value || 'Your headline here';
    document.getElementById('headlineCount').textContent = this.value.length;
});
document.querySelector('[name="primary_text"]').addEventListener('input', function() {
    document.getElementById('previewPrimaryText').textContent = this.value || 'Your primary text will appear here...';
});
document.querySelector('[name="description"]').addEventListener('input', function() {
    document.getElementById('previewDescription').textContent = this.value || 'Your description here';
});
document.querySelector('[name="display_url"]').addEventListener('input', function() {
    document.getElementById('previewDisplayUrl').textContent = this.value || 'yourwebsite.com';
});
document.querySelector('[name="call_to_action"]').addEventListener('change', function() {
    document.getElementById('previewCTA').textContent = this.options[this.selectedIndex].text;
});
</script>
@endpush
@endsection
