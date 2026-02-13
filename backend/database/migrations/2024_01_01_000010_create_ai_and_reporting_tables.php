<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI Insights storage
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // forecast, anomaly, budget_recommendation, nl_insight
            $table->string('severity')->default('info'); // info, warning, critical
            $table->string('title');
            $table->text('description');
            $table->json('data')->nullable();
            $table->json('affected_campaigns')->nullable();
            $table->string('suggested_action')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type', 'is_read']);
            $table->index(['organization_id', 'severity']);
            $table->index(['organization_id', 'created_at']);
        });

        // Reports
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // performance, creative, audience, custom
            $table->json('config'); // report configuration
            $table->json('filters')->nullable();
            $table->string('schedule')->nullable(); // daily, weekly, monthly
            $table->string('format')->default('pdf'); // pdf, csv, xlsx
            $table->string('status')->default('draft'); // draft, scheduled, generating, completed
            $table->string('file_path')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->json('recipients')->nullable(); // email addresses
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type']);
            $table->index(['status', 'schedule']);
        });

        // Alerts
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // metric_threshold, anomaly, budget, automation
            $table->string('severity')->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('channel')->default('in_app'); // in_app, email, slack
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'is_read', 'created_at']);
            $table->index(['organization_id', 'type']);
        });

        // Audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // create, update, delete, login, etc.
            $table->string('entity_type'); // campaign, automation_rule, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
        });

        // Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->json('events'); // ['campaign.updated', 'automation.triggered', etc.]
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('ai_insights');
    }
};
