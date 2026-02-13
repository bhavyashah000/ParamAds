<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, paused, draft
            $table->string('scope')->default('campaign'); // campaign, adset, ad
            $table->string('platform')->nullable(); // meta, google, all
            $table->json('conditions'); // [{metric, operator, value, time_window, logic}]
            $table->string('condition_logic')->default('AND'); // AND, OR
            $table->json('actions'); // [{type, params}]
            $table->string('time_window')->default('24h'); // 1h, 6h, 12h, 24h, 3d, 7d
            $table->integer('check_interval_minutes')->default(60);
            $table->integer('cooldown_minutes')->default(360); // min time between executions
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->json('target_ids')->nullable(); // specific campaign/adset/ad IDs
            $table->boolean('apply_to_all')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['status', 'check_interval_minutes']);
        });

        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_rule_id')->constrained()->cascadeOnDelete();
            $table->string('target_type'); // campaign, adset, ad
            $table->unsignedBigInteger('target_id');
            $table->string('action_type'); // pause, activate, budget_increase, budget_decrease, alert
            $table->json('condition_snapshot'); // conditions that triggered
            $table->json('action_result'); // what was done
            $table->string('status')->default('success'); // success, failed, skipped
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['automation_rule_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automation_rules');
    }
};
