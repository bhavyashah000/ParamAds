<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_creative_id')->nullable();
            $table->string('type'); // image, video, carousel, text
            $table->string('name')->nullable();
            $table->text('headline')->nullable();
            $table->text('body_copy')->nullable();
            $table->string('cta')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('video_duration')->nullable(); // seconds
            $table->json('platform_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type']);
            $table->index(['ad_account_id', 'platform']);
        });

        Schema::create('creative_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creative_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpc', 10, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);
            $table->decimal('cpa', 10, 4)->default(0);
            $table->integer('video_views')->default(0);
            $table->decimal('video_completion_rate', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['creative_id', 'date']);
            $table->index(['organization_id', 'date']);
        });

        Schema::create('creative_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creative_id')->constrained()->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->default(0); // 0-100
            $table->decimal('engagement_score', 5, 2)->default(0);
            $table->decimal('conversion_score', 5, 2)->default(0);
            $table->decimal('efficiency_score', 5, 2)->default(0);
            $table->string('fatigue_status')->default('fresh'); // fresh, active, declining, fatigued
            $table->decimal('fatigue_score', 5, 2)->default(0);
            $table->decimal('ctr_trend', 8, 4)->default(0); // slope of CTR over time
            $table->decimal('frequency_avg', 8, 4)->default(0);
            $table->json('trend_data')->nullable();
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->index(['organization_id', 'fatigue_status']);
            $table->index(['creative_id', 'scored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creative_scores');
        Schema::dropIfExists('creative_metrics');
        Schema::dropIfExists('creatives');
    }
};
