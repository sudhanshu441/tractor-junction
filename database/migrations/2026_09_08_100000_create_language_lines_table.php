<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable UI strings, specified in the ERD alongside `translations`.
 *
 * `translations` carries content fields (a product's Hindi name); this carries
 * interface copy, so operations can fix a clumsy Hindi label without a deploy.
 * A row here overrides the matching line in lang/{locale}.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_lines', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('group', 60)->default('*');   // "*" = the JSON strings file
            $table->string('key', 500);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['locale', 'group']);
        });

        // MySQL cannot index a 500-char string in full under utf8mb4; the
        // uniqueness that matters is per locale and the first 191 characters.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                'CREATE UNIQUE INDEX language_lines_unique ON language_lines (locale, `group`, `key`(191))'
            );
        } else {
            Schema::table('language_lines', function (Blueprint $table) {
                $table->unique(['locale', 'group', 'key'], 'language_lines_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('language_lines');
    }
};
