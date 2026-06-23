<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update brands table - add media reference and SEO fields
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('logo_media_id')->nullable()->after('is_visible')->constrained('media')->nullOnDelete();
            $table->string('meta_title')->nullable()->after('logo_media_id');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('website_url')->nullable()->after('meta_description');
            $table->unsignedInteger('sort_order')->default(0)->after('website_url');
        });

        // Update categories table - add media references and styling fields
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('image_media_id')->nullable()->after('sort_order')->constrained('media')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->after('image_media_id')->constrained('media')->nullOnDelete();
            $table->string('color')->nullable()->after('banner_media_id');
            $table->string('icon')->nullable()->after('color');
        });

        // Update products table - add rich catalog fields
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_description')->nullable()->after('description');
            $table->decimal('cost_price', 12, 2)->nullable()->after('compare_at_price');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->boolean('is_digital')->default(false)->after('is_featured');
            $table->string('tax_class_id')->nullable()->after('is_digital');
            $table->decimal('weight', 10, 3)->default(0)->after('tax_class_id');
            $table->decimal('length', 10, 3)->nullable()->after('weight');
            $table->decimal('width', 10, 3)->nullable()->after('length');
            $table->decimal('height', 10, 3)->nullable()->after('width');
            $table->string('weight_unit')->default('kg')->after('height');
            $table->string('dimension_unit')->default('cm')->after('weight_unit');
            $table->string('barcode')->nullable()->unique()->after('dimension_unit');
            $table->string('mpn')->nullable()->after('barcode');
            $table->string('gtin')->nullable()->after('mpn');
            $table->foreignId('primary_image_media_id')->nullable()->after('gtin')->constrained('media')->nullOnDelete();
            $table->string('seo_slug')->nullable()->after('primary_image_media_id');
            $table->string('canonical_url')->nullable()->after('seo_slug');
            $table->unsignedBigInteger('view_count')->default(0)->after('canonical_url');
            $table->decimal('average_rating', 3, 2)->default(0)->after('view_count');
            $table->unsignedInteger('review_count')->default(0)->after('average_rating');
            $table->timestamp('published_at')->nullable()->after('review_count');
        });

        // Update product_variants table - add media and cost fields
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('compare_at_price');
            $table->foreignId('image_media_id')->nullable()->after('is_default')->constrained('media')->nullOnDelete();
            $table->string('barcode')->nullable()->after('image_media_id');
            $table->decimal('weight', 10, 3)->nullable()->after('barcode');
        });

        // Create product_images table - ordered gallery images
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Create product_reviews table
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'is_approved', 'rating']);
            $table->index(['product_id', 'is_approved', 'created_at']);
        });

        // Create product_relations table (related, cross-sell, up-sell)
        Schema::create('product_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('relation_type'); // related, cross_sell, up_sell
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'relation_type']);
            $table->index(['product_id', 'relation_type']);
        });

        // Create product_collections table
        Schema::create('product_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('condition_type')->default('manual'); // manual, automated
            $table->json('conditions')->nullable(); // For automated collections
            $table->timestamps();
            $table->softDeletes();
        });

        // Create product_collection_items pivot table
        Schema::create('product_collection_items', function (Blueprint $table) {
            $table->foreignId('product_collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['product_collection_id', 'product_id']);
        });

        // Create product_seo table
        Schema::create('product_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique()->cascadeOnDelete();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->foreignId('twitter_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('schema_markup')->nullable();
            $table->string('robots_meta')->default('index, follow');
            $table->timestamps();
        });

        // Create product_pricing_tiers table
        Schema::create('product_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('customer_group_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'variant_id', 'customer_group_id']);
            $table->index(['product_id', 'min_quantity', 'max_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pricing_tiers');
        Schema::dropIfExists('product_seo');
        Schema::dropIfExists('product_collection_items');
        Schema::dropIfExists('product_collections');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_images');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_media_id');
            $table->dropColumn(['cost_price', 'barcode', 'weight']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_image_media_id');
            $table->dropColumn([
                'short_description', 'cost_price', 'meta_keywords', 'is_digital',
                'tax_class_id', 'weight', 'length', 'width', 'height',
                'weight_unit', 'dimension_unit', 'barcode', 'mpn', 'gtin',
                'seo_slug', 'canonical_url', 'view_count', 'average_rating',
                'review_count', 'published_at',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_media_id');
            $table->dropConstrainedForeignId('banner_media_id');
            $table->dropColumn(['color', 'icon']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logo_media_id');
            $table->dropColumn(['meta_title', 'meta_description', 'website_url', 'sort_order']);
        });
    }
};
