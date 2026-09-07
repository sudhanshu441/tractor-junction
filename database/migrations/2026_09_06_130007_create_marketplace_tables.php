<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('used_listings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();      // KJ-U-000123
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('seller_type', ['owner', 'dealer', 'broker'])->default('owner');
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            // matched catalogue model — specs and images are inherited from it
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Nullable because a draft exists before the seller has told us what the
            // machine is; both are set when the listing is submitted for review.
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->year('manufacturing_year')->nullable();
            $table->unsignedInteger('engine_hours')->nullable();
            $table->decimal('hp', 6, 2)->nullable();
            $table->enum('condition', ['excellent', 'good', 'average', 'needs_repair'])->default('good');
            $table->enum('tyre_condition_front', ['new', 'good', 'worn'])->nullable();
            $table->enum('tyre_condition_rear', ['new', 'good', 'worn'])->nullable();
            $table->boolean('has_rc')->default(false);
            $table->boolean('has_insurance')->default(false);
            $table->date('insurance_valid_till')->nullable();
            $table->boolean('is_financed')->default(false);
            $table->string('registration_number', 20)->nullable(); // masked in public views
            $table->decimal('expected_price', 12, 2)->nullable();
            $table->decimal('negotiable_to', 12, 2)->nullable();
            $table->boolean('is_price_negotiable')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pincode', 6)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'live', 'sold', 'expired', 'rejected', 'blocked'])
                ->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_verified')->default(false);     // inspection passed
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_till')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('lead_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['state_id', 'district_id', 'status'], 'used_listings_geo_status_index');
            $table->index(['brand_id', 'category_id', 'status'], 'used_listings_brand_cat_index');
            $table->index('expected_price');
            $table->index('manufacturing_year');
            $table->index(['user_id', 'created_at']);
            $table->index(['expires_at', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('used_listings', fn (Blueprint $t) => $t->fullText('title'));
        }

        Schema::create('used_listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->enum('angle', ['front', 'rear', 'left', 'right', 'engine', 'tyre', 'meter', 'document', 'other'])
                ->default('other');
            $table->boolean('is_primary')->default(false);
            $table->string('image_hash', 64)->nullable();   // perceptual hash, duplicate detection
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['used_listing_id', 'sort_order']);
            $table->index('image_hash');
        });

        Schema::create('used_listing_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_listing_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('used_listing_id');
        });

        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reason', ['sold', 'fake', 'wrong_price', 'spam', 'abusive', 'duplicate', 'other']);
            $table->text('details')->nullable();
            $table->enum('status', ['open', 'reviewed', 'actioned', 'dismissed'])->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('listing_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });

        Schema::create('inspection_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40);   // Engine | Transmission | Hydraulics | Tyres | Body | Documents | Electricals
            $table->string('name');
            $table->decimal('weight', 5, 2)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section', 'sort_order']);
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();
            $table->foreignId('used_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['requested', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('requested');
            $table->decimal('overall_score', 5, 2)->nullable();   // 0-100
            $table->enum('grade', ['A', 'B', 'C', 'D'])->nullable();
            $table->decimal('valuation_min', 12, 2)->nullable();
            $table->decimal('valuation_max', 12, 2)->nullable();
            $table->text('summary')->nullable();
            $table->string('report_pdf_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_checklist_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);   // 0-10
            $table->text('remarks')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'inspection_checklist_item_id'], 'inspection_item_unique');
        });

        // Admin-editable multipliers behind the fair-price estimate
        Schema::create('valuation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('factor_type', ['age', 'hours', 'condition', 'region', 'brand']);
            $table->string('key', 40);          // year_1 | 0-1000 | grade_A | RJ | mahindra
            $table->decimal('multiplier', 6, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['factor_type', 'key']);
        });
    }

    public function down(): void
    {
        foreach ([
            'valuation_rules', 'inspection_items', 'inspections', 'inspection_checklist_items',
            'listing_boosts', 'listing_reports', 'used_listing_status_logs',
            'used_listing_images', 'used_listings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
