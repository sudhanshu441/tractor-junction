<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Free / Silver / Gold
            $table->string('slug')->unique();
            $table->enum('audience', ['dealer', 'seller'])->default('dealer');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly', 'one_time'])->default('monthly');
            $table->unsignedInteger('lead_limit')->default(0);        // per month, 0 = unlimited
            $table->unsignedInteger('daily_lead_cap')->default(0);
            $table->unsignedInteger('inventory_limit')->default(0);
            $table->unsignedInteger('featured_listing_count')->default(0);
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();          // KJ-D-00123
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('business_name');
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->enum('dealer_type', ['authorised', 'multi_brand', 'used_only', 'implement'])->default('authorised');
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 12)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 15);
            $table->string('alternate_mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pincode', 6)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('logo')->nullable();
            $table->text('about')->nullable();
            $table->json('working_hours')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->text('verification_remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('lead_count')->default(0);
            // rolling score from lead response times — feeds routing priority
            $table->decimal('response_score', 4, 2)->default(5);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['state_id', 'district_id', 'verification_status'], 'dealers_geo_status_index');
            $table->index(['is_featured', 'rating_avg']);
        });

        Schema::create('dealer_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pincode', 6)->nullable();
            $table->string('mobile', 15)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['dealer_id', 'is_active']);
        });

        Schema::create('dealer_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'sales'])->default('sales');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dealer_id', 'user_id']);
        });

        Schema::create('dealer_brand', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->date('authorised_since')->nullable();

            $table->unique(['dealer_id', 'brand_id']);
        });

        Schema::create('dealer_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->enum('availability', ['in_stock', 'out_of_stock', 'on_order'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dealer_id', 'product_id', 'product_variant_id'], 'dealer_inventory_unique');
            $table->index(['product_id', 'availability']);
        });

        Schema::create('dealer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->enum('doc_type', ['gst', 'pan', 'shop_licence', 'authorisation_letter', 'photo', 'other']);
            $table->string('file_path');       // private disk only
            $table->string('original_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('payable');            // DealerSubscription | ListingBoost
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('gateway', 30)->default('razorpay');
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->enum('status', ['created', 'paid', 'failed', 'refunded'])->default('created');
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('dealer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedInteger('leads_consumed')->default(0);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending_payment'])->default('active');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['dealer_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach ([
            'dealer_subscriptions', 'payments', 'dealer_documents', 'dealer_inventory',
            'dealer_brand', 'dealer_users', 'dealer_branches', 'dealers', 'plans',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
