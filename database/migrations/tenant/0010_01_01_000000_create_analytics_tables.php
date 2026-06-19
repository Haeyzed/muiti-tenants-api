<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('conversion_rate', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['flash_sale_id', 'recorded_on']);
        });

        Schema::create('drop_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('queue_entries')->default(0);
            $table->unsignedInteger('checkouts_completed')->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->unsignedInteger('units_sold')->default(0);
            $table->timestamps();
        });

        Schema::create('traffic_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('recorded_on');
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('bounce_count')->default(0);
            $table->timestamps();

            $table->unique('recorded_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_analytics');
        Schema::dropIfExists('drop_analytics');
        Schema::dropIfExists('conversion_metrics');
    }
};
