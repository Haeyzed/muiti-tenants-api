<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('max_concurrent_sessions')->default(100);
            $table->unsignedInteger('session_ttl_seconds')->default(600);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_queue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token')->unique();
            $table->unsignedInteger('queue_position')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamps();

            $table->index(['checkout_queue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
        Schema::dropIfExists('checkout_queues');
    }
};
