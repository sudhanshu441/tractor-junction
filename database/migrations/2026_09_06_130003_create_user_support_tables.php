<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->decimal('land_holding_acres', 8, 2)->nullable();
            $table->enum('farming_type', ['irrigated', 'rain_fed', 'orchard', 'mixed'])->nullable();
            $table->json('crops')->nullable();
            $table->string('address_line')->nullable();
            $table->string('pincode', 6)->nullable();
            $table->json('preferences')->nullable(); // notification + marketing opt-ins
            $table->timestamps();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid')->nullable();
            $table->enum('platform', ['web', 'android', 'ios'])->default('web');
            $table->string('fcm_token')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 15);
            $table->string('otp_hash');
            $table->enum('purpose', ['login', 'register', 'listing', 'lead', 'loan', 'profile'])->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            // datetime, not timestamp — a TIMESTAMP NOT NULL column silently gains
            // ON UPDATE CURRENT_TIMESTAMP on MySQL, which would move an OTP's expiry
            // every time the row was touched.
            $table->dateTime('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['mobile', 'purpose', 'expires_at']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('context', ['new', 'used', 'dealer'])->default('used');
            $table->json('filters');
            $table->boolean('alert_enabled')->default(false);
            $table->timestamp('last_alert_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('user_profiles');
    }
};
