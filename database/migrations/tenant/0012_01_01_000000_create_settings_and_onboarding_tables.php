<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('business_type')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 30)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->timestamps();
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->nullable();
            $table->text('store_description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('timezone')->nullable();
            $table->string('language_code', 5)->nullable();
            $table->timestamps();
        });

        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();
            $table->json('theme')->nullable();
            $table->timestamps();
        });

        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption', 10)->nullable();
            $table->json('templates')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('admin_alerts_enabled')->default(true);
            $table->json('channels')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20)->default('INV');
            $table->string('number_format', 50)->default('{PREFIX}-{YEAR}-{SEQUENCE}');
            $table->text('footer')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('next_sequence')->default(1);
            $table->timestamps();
        });

        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->string('current_step', 50)->default('business_info');
            $table->json('completed_steps')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
        Schema::dropIfExists('invoice_settings');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('email_settings');
        Schema::dropIfExists('branding_settings');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('business_settings');
    }
};
