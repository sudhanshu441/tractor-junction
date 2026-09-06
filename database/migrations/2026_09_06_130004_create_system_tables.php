<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'text', 'int', 'bool', 'json', 'file'])->default('string');
            $table->string('label')->nullable();
            $table->boolean('is_public')->default(false); // safe to expose to the front-end
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('group');
        });

        // Polymorphic media for products, listings, dealers, blogs, banners…
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('collection')->default('gallery');
            $table->string('file_name');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('conversions')->nullable(); // thumb / card / detail paths
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'collection'], 'media_model_collection_index');
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // lead.created, listing.approved…
            $table->string('name');
            $table->json('channels'); // ["sms","email","database"]
            $table->text('sms_body')->nullable();
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable();
            $table->string('push_title')->nullable();
            $table->string('push_body')->nullable();
            $table->string('whatsapp_template_id')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('recipient');
            $table->text('payload')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed', 'delivered'])->default('queued');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('recipient');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique();
            $table->string('to_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('referer')->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('url');
        });

        // Hindi / transliteration synonyms applied before a FULLTEXT query
        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term');            // महिंद्रा, mhindra
            $table->string('canonical');       // mahindra
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('term');
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('context', 30)->default('global');
            $table->unsignedInteger('results_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['term', 'created_at']);
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('viewable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('referer')->nullable();
            $table->string('utm_source', 60)->nullable();
            $table->date('viewed_on');
            $table->timestamps();

            $table->index(['viewable_type', 'viewable_id', 'viewed_on'], 'page_views_viewable_date_index');
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 5);
            $table->string('field', 60);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translations_unique');
        });
    }

    public function down(): void
    {
        foreach ([
            'translations', 'page_views', 'search_logs', 'search_synonyms', 'not_found_logs',
            'redirects', 'notifications', 'notification_logs', 'notification_templates',
            'media', 'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
