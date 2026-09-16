<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties an anonymous browsing journey to the lead it eventually becomes.
 *
 * A session id dies when the browser closes; a visitor id survives, so somebody
 * who reads about 45 HP tractors on Monday and leaves their number on Thursday
 * arrives at the sales desk as one person with a history, not two strangers.
 *
 * This identifies a *browser*, never a person. A name and a mobile number only
 * ever enter the system because somebody typed them into a form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->string('visitor_id', 36)->nullable()->after('session_id');
            $table->string('url', 500)->nullable()->after('visitor_id');
            $table->string('intent', 30)->nullable()->after('url');   // buy | sell | finance | insurance | dealer

            $table->index(['visitor_id', 'created_at'], 'page_views_visitor_index');
            $table->index(['intent', 'viewed_on'], 'page_views_intent_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            // Set when a lead arrives from a browser we had already seen.
            $table->string('visitor_id', 36)->nullable()->after('user_id');
            $table->index('visitor_id');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex('page_views_visitor_index');
            $table->dropIndex('page_views_intent_index');
            $table->dropColumn(['visitor_id', 'url', 'intent']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['visitor_id']);
            $table->dropColumn('visitor_id');
        });
    }
};
