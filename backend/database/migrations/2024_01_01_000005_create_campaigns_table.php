<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // meta, google
            $table->string('platform_campaign_id');
            $table->string('name');
            $table->string('status')->default('active'); // active, paused, deleted, archived
            $table->string('objective')->nullable(); // conversions, traffic, awareness, etc.
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('lifetime_budget', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('targeting')->nullable();
            $table->json('platform_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ad_account_id', 'platform_campaign_id']);
            $table->index(['organization_id', 'platform', 'status']);
            $table->index(['ad_account_id', 'status']);
        });

        // Ad Sets / Ad Groups
        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_adset_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->json('targeting')->nullable();
            $table->json('platform_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['campaign_id', 'platform_adset_id']);
            $table->index(['organization_id', 'status']);
        });

        // Ads
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_ad_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('creative_type')->nullable(); // image, video, carousel
            $table->text('headline')->nullable();
            $table->text('body')->nullable();
            $table->string('cta')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->json('platform_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ad_set_id', 'platform_ad_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_sets');
        Schema::dropIfExists('campaigns');
    }
};
