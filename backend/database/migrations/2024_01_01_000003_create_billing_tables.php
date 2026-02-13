<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('interval')->default('monthly'); // monthly, yearly
            $table->json('features');
            $table->integer('max_ad_accounts')->default(1);
            $table->integer('max_campaigns')->default(10);
            $table->integer('max_automation_rules')->default(5);
            $table->integer('max_team_members')->default(1);
            $table->boolean('has_ai_features')->default(false);
            $table->boolean('has_creative_intelligence')->default(false);
            $table->boolean('has_audience_intelligence')->default(false);
            $table->boolean('has_agency_features')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Audit log for billing events
        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // subscription.created, payment.succeeded, etc.
            $table->string('stripe_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('plans');
    }
};
