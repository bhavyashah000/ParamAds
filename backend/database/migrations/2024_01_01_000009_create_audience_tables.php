<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pixel connections
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_pixel_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('platform_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'platform_pixel_id']);
        });

        // Audiences
        Schema::create('audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_audience_id')->nullable();
            $table->string('name');
            $table->string('type'); // custom, lookalike, saved, retargeting
            $table->string('subtype')->nullable(); // website_visitors, add_to_cart, purchasers, etc.
            $table->integer('size')->default(0);
            $table->string('status')->default('active');
            $table->json('rules')->nullable(); // audience definition rules
            $table->json('platform_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type']);
            $table->index(['ad_account_id', 'platform']);
        });

        // Audience performance metrics
        Schema::create('audience_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audience_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('size')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('roas', 10, 4)->default(0);
            $table->decimal('frequency', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['audience_id', 'date']);
            $table->index(['organization_id', 'date']);
        });

        // Audience overlap tracking
        Schema::create('audience_overlaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audience_a_id')->constrained('audiences')->cascadeOnDelete();
            $table->foreignId('audience_b_id')->constrained('audiences')->cascadeOnDelete();
            $table->decimal('overlap_percentage', 6, 2)->default(0);
            $table->integer('overlap_size')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['audience_a_id', 'audience_b_id']);
        });

        // Retargeting funnels
        Schema::create('retargeting_funnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('stages'); // [{name, audience_id, days_window, budget_allocation}]
            $table->string('status')->default('active');
            $table->json('automation_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });

        // Retargeting rules
        Schema::create('retargeting_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retargeting_funnel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audience_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('conditions'); // [{metric, operator, value}]
            $table->json('actions'); // [{type, params}]
            $table->string('status')->default('active');
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retargeting_rules');
        Schema::dropIfExists('retargeting_funnels');
        Schema::dropIfExists('audience_overlaps');
        Schema::dropIfExists('audience_metrics');
        Schema::dropIfExists('audiences');
        Schema::dropIfExists('pixels');
    }
};
