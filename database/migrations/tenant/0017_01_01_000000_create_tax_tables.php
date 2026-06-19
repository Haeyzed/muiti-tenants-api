<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->decimal('rate', 8, 4);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('tax_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2);
            $table->string('state_code', 10)->nullable();
            $table->string('postal_code_pattern')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'state_code']);
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('applies_to', 30)->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('category_tax_class', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'tax_class_id']);
        });

        Schema::create('product_tax_class', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tax_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tax_class');
        Schema::dropIfExists('category_tax_class');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_regions');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_classes');
    }
};
