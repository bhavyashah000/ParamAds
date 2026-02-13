<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Granular metrics (hourly/daily raw data)
        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('cpm', 10, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);
            $table->decimal('cpa', 10, 4)->default(0);
            $table->integer('reach')->default(0);
            $table->decimal('frequency', 8, 4)->default(0);
            $table->integer('video_views')->default(0);
            $table->integer('link_clicks')->default(0);
            $table->integer('add_to_cart')->default(0);
            $table->integer('purchases')->default(0);
            $table->json('extra_metrics')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'date', 'platform'], 'campaign_metrics_unique');
            $table->index(['organization_id', 'date']);
            $table->index(['campaign_id', 'date']);
            $table->index(['ad_account_id', 'date']);
            $table->index(['platform', 'date']);
        });

        // Ad Set level metrics
        Schema::create('adset_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('cpm', 10, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);
            $table->decimal('cpa', 10, 4)->default(0);
            $table->json('extra_metrics')->nullable();
            $table->timestamps();

            $table->unique(['ad_set_id', 'date', 'platform'], 'adset_metrics_unique');
            $table->index(['organization_id', 'date']);
        });

        // Ad level metrics
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('cpm', 10, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);
            $table->decimal('cpa', 10, 4)->default(0);
            $table->json('extra_metrics')->nullable();
            $table->timestamps();

            $table->unique(['ad_id', 'date', 'platform'], 'ad_metrics_unique');
            $table->index(['organization_id', 'date']);
        });

        // Daily aggregated summaries for fast dashboard queries
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform')->nullable();
            $table->date('date');
            $table->integer('total_campaigns')->default(0);
            $table->integer('active_campaigns')->default(0);
            $table->decimal('total_spend', 14, 4)->default(0);
            $table->decimal('total_revenue', 14, 4)->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('avg_cpc', 10, 4)->default(0);
            $table->decimal('avg_cpm', 10, 4)->default(0);
            $table->decimal('avg_ctr', 8, 4)->default(0);
            $table->decimal('avg_roas', 10, 4)->default(0);
            $table->decimal('avg_cpa', 10, 4)->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'ad_account_id', 'date', 'platform'], 'daily_summary_unique');
            $table->index(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('ad_metrics');
        Schema::dropIfExists('adset_metrics');
        Schema::dropIfExists('campaign_metrics');
    }
};
