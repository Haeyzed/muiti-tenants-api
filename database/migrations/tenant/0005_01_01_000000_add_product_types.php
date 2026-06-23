<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add product_type to products table and type-specific fields
        Schema::table('products', function (Blueprint $table) {
            // Replace is_digital with product_type enum
            $table->string('product_type', 20)->default('standard')->after('is_featured');
            $table->index('product_type');

            // Digital product fields
            $table->unsignedInteger('download_limit')->nullable()->after('product_type');
            $table->unsignedInteger('download_expiry_days')->nullable()->after('download_limit');
            $table->foreignId('preview_media_id')->nullable()->after('download_expiry_days')->constrained('media')->nullOnDelete();

            // Service product fields
            $table->unsignedInteger('duration_minutes')->nullable()->after('preview_media_id');
            $table->unsignedInteger('buffer_minutes')->nullable()->after('duration_minutes');
            $table->unsignedInteger('max_participants')->nullable()->after('buffer_minutes');
            $table->string('location_type')->nullable()->after('max_participants'); // physical, virtual, both
            $table->text('service_location')->nullable()->after('location_type');

            // Combo product fields
            $table->boolean('allow_partial_combo')->default(false)->after('service_location');

            // YouTube video support
            $table->string('youtube_url')->nullable()->after('allow_partial_combo');
        });

        // Remove old is_digital column (data migrated to product_type)
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_digital');
        });

        // Create product_digital_files table
        Schema::create('product_digital_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Create product_combo_items table
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('included_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('included_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_optional')->default(false);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'included_product_id', 'included_variant_id']);
            $table->index(['product_id', 'sort_order']);
        });

        // Create product_service_providers table
        Schema::create('product_service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'provider_id']);
        });

        // Create product_videos table (for multiple YouTube videos)
        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('video_url');
            $table->string('video_id', 20);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });

        // Update product_images to support gallery with alt text and captions
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('caption')->nullable()->after('alt_text');
            $table->boolean('is_primary_gallery')->default(false)->after('caption');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_videos');
        Schema::dropIfExists('product_service_providers');
        Schema::dropIfExists('product_combo_items');
        Schema::dropIfExists('product_digital_files');

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['caption', 'is_primary_gallery']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preview_media_id');
            $table->dropColumn([
                'product_type',
                'download_limit',
                'download_expiry_days',
                'duration_minutes',
                'buffer_minutes',
                'max_participants',
                'location_type',
                'service_location',
                'allow_partial_combo',
                'youtube_url',
            ]);
            $table->boolean('is_digital')->default(false)->after('is_featured');
        });
    }
};
