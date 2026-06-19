<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flash_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('back_in_stock');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('waitlist_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waitlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('status', 20)->default('active');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['waitlist_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_subscribers');
        Schema::dropIfExists('waitlists');
    }
};
