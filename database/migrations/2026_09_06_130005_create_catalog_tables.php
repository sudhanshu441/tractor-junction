<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('country', 60)->nullable();
            $table->year('founded_year')->nullable();
            $table->string('website')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_popular']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('type', ['tractor', 'implement', 'harvester', 'tyre', 'farm_tool']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });

        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['brand_id', 'category_id']);
        });

        Schema::create('spec_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Engine, Transmission, Hydraulics…
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('spec_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spec_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // No. of Cylinders
            $table->string('slug')->unique();
            $table->enum('data_type', ['int', 'decimal', 'string', 'boolean', 'select', 'json'])->default('string');
            $table->string('unit', 20)->nullable();     // HP, cc, kg, mm, L
            $table->json('options')->nullable();        // for select
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_comparable')->default(true);
            $table->boolean('is_key_spec')->default(false); // shown in the summary strip
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('spec_group_id');
        });

        // Which attributes apply to which category — drives the auto-built admin form
        Schema::create('category_spec_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spec_attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['category_id', 'spec_attribute_id'], 'category_attribute_unique');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model_code', 60)->nullable();
            $table->enum('status', ['available', 'upcoming', 'discontinued'])->default('available');
            $table->decimal('hp_min', 6, 2)->nullable();
            $table->decimal('hp_max', 6, 2)->nullable();
            $table->decimal('price_min', 12, 2)->nullable(); // ex-showroom
            $table->decimal('price_max', 12, 2)->nullable();
            $table->year('launch_year')->nullable();
            $table->date('expected_launch_date')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('highlights')->nullable();
            $table->string('brochure_path')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);   // cached aggregate
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('lead_count')->default(0);
            $table->unsignedInteger('popularity_score')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index(['category_id', 'status', 'is_active']);
            $table->index(['is_popular', 'popularity_score']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('products', fn (Blueprint $t) => $t->fullText('name'));
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // 2WD / 4WD / Power steering
            $table->string('slug');
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'slug']);
        });

        // Typed EAV: numeric values stay index-able so HP/price range filters are fast
        Schema::create('product_spec_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('spec_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value_string')->nullable();
            $table->decimal('value_number', 14, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'spec_attribute_id'], 'product_spec_unique');
            $table->index(['spec_attribute_id', 'value_number']);
        });

        // Denormalised flat table that every facet query actually hits
        Schema::create('product_filter_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('hp', 6, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('wheel_drive', 10)->nullable();
            $table->unsignedTinyInteger('cylinders')->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->decimal('lift_capacity', 8, 2)->nullable();
            $table->string('transmission', 40)->nullable();
            $table->boolean('power_steering')->default(false);
            $table->boolean('ac_cabin')->default(false);
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->index(['category_id', 'hp']);
            $table->index(['category_id', 'price']);
            $table->index(['brand_id', 'hp']);
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnDelete(); // null = national
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('ex_showroom', 12, 2);
            $table->decimal('rto_charges', 10, 2)->default(0);
            $table->decimal('insurance_amount', 10, 2)->default(0);
            $table->decimal('other_charges', 10, 2)->default(0);
            $table->decimal('on_road_price', 12, 2)->default(0); // computed on save
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'state_id', 'is_active']);
            $table->index(['state_id', 'effective_from']);
        });

        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->decimal('new_price', 12, 2);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competitor_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['product_id', 'competitor_product_id'], 'product_competitor_unique');
        });

        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('youtube_id', 30);
            $table->string('thumbnail')->nullable();
            $table->enum('type', ['review', 'walkaround', 'comparison', 'demo'])->default('review');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'product_videos', 'product_competitors', 'product_faqs', 'product_features',
            'product_price_history', 'product_prices', 'product_filter_cache',
            'product_spec_values', 'product_variants', 'products',
            'category_spec_attribute', 'spec_attributes', 'spec_groups',
            'brand_category', 'categories', 'brands',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
