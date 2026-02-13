<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sub-accounts for agency model
        Schema::create('sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('child_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->json('permissions')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['parent_organization_id', 'child_organization_id']);
        });

        // White-label settings
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color', 20)->default('#3B82F6');
            $table->string('secondary_color', 20)->default('#1E40AF');
            $table->string('custom_domain')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_url')->nullable();
            $table->json('custom_css')->nullable();
            $table->json('email_templates')->nullable();
            $table->boolean('hide_powered_by')->default(false);
            $table->timestamps();
        });

        // Scheduled reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('type'); // performance, creative, audience, budget, executive
            $table->string('frequency'); // daily, weekly, monthly
            $table->string('format')->default('pdf'); // pdf, csv, xlsx
            $table->json('filters')->nullable();
            $table->json('recipients'); // email addresses
            $table->json('sections')->nullable(); // which sections to include
            $table->string('status')->default('active');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();
        });

        // Report history
        Schema::create('report_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('file_url')->nullable();
            $table->string('status'); // generated, sent, failed
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events'); // which events to trigger on
            $table->boolean('active')->default(true);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });

        // Webhook logs
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->boolean('success');
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });

        // Team invitations
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users');
            $table->string('email');
            $table->string('role')->default('viewer');
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending'); // pending, accepted, expired
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Activity log
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, etc.
            $table->string('resource_type'); // campaign, rule, report, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('report_history');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('white_label_settings');
        Schema::dropIfExists('sub_accounts');
    }
};
