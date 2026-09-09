<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // organic | google_ads | facebook | referral | direct | app
            $table->string('slug')->unique();
            $table->string('utm_source', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // One polymorphic table carries every enquiry type in the product
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();     // KJ-L-000456
            $table->enum('type', [
                'new_product', 'used_listing', 'dealer', 'loan', 'insurance',
                'callback', 'offer', 'contact', 'sell_request',
            ]);
            $table->nullableMorphs('leadable');               // Product | UsedListing | Dealer | Offer
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile', 15);
            $table->boolean('mobile_verified')->default(false);
            $table->string('email')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();                 // budget, timeline, financing_needed, utm_*
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('web');    // web | mobile_web | api | app | phone | whatsapp
            $table->enum('status', [
                'new', 'assigned', 'contacted', 'qualified', 'converted', 'lost', 'duplicate', 'invalid',
            ])->default('new');
            $table->enum('quality', ['hot', 'warm', 'cold'])->nullable();
            $table->string('lost_reason')->nullable();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->date('next_follow_up_at')->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_value', 12, 2)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'created_at']);
            $table->index('mobile');
            $table->index(['district_id', 'status']);
            $table->index('next_follow_up_at');
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->enum('assignee_type', ['dealer', 'staff', 'seller']);
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('routing_rule_id')->nullable();  // explains why this assignee was picked
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            // datetime, not timestamp: as TIMESTAMP NOT NULL this would silently
            // gain ON UPDATE CURRENT_TIMESTAMP on MySQL and every status change
            // would reset the assignment time the SLA is measured from.
            $table->dateTime('assigned_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['dealer_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('lead_id');
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('activity', ['note', 'call', 'sms', 'whatsapp', 'email', 'status_change', 'assignment', 'visit']);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('call_duration_seconds')->nullable();
            $table->string('call_recording_url')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });

        // Ordered rules; first match wins. Kept explainable, not hard-coded.
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('priority')->default(10);
            $table->string('lead_type', 20)->nullable();       // null = any
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('assignee_type', ['dealer', 'staff', 'seller'])->default('dealer');
            $table->foreignId('fallback_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('daily_cap')->default(0);  // 0 = use the dealer's plan cap
            $table->unsignedInteger('response_sla_minutes')->default(120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        foreach (['routing_rules', 'lead_activities', 'lead_assignments', 'leads', 'lead_sources'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
