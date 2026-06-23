// Add these to your tenant.php routes file inside the auth:sanctum group

        // -------------------------------------------------------------------------
        // Products (enhanced with types, gallery, videos)
        // -------------------------------------------------------------------------

        Route::prefix('products')->group(function (): void {
            Route::get('featured', [ProductController::class, 'featured']);
            Route::get('types', [ProductController::class, 'types']);
            Route::get('slug/{slug}', [ProductController::class, 'showBySlug']);
            Route::get('{product}/related', [ProductController::class, 'relatedProducts']);
            Route::get('{product}/reviews', [ProductController::class, 'reviews']);
            Route::post('{product}/reviews', [ProductController::class, 'storeReview']);
            Route::post('{product}/reviews/{review}/approve', [ProductController::class, 'approveReview']);
            Route::get('{product}/structured-data', [ProductController::class, 'structuredData']);

            Route::delete('bulk', [ProductController::class, 'destroyMany']);
            Route::post('bulk-restore', [ProductController::class, 'restoreMany']);
            Route::post('{product}/restore', [ProductController::class, 'restore'])->withTrashed();
            Route::delete('{product}/force', [ProductController::class, 'forceDestroy'])->withTrashed();
        });

        Route::apiResource('products', ProductController::class);

        // Product variants
        Route::post('products/{product}/variants', [ProductController::class, 'storeVariant']);
        Route::put('products/{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
        Route::delete('products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant']);

        // -------------------------------------------------------------------------
        // Categories (enhanced with media library)
        // -------------------------------------------------------------------------

        Route::prefix('categories')->group(function (): void {
            Route::get('tree', [CategoryController::class, 'tree']);
            Route::delete('bulk', [CategoryController::class, 'destroyMany']);
            Route::post('bulk-restore', [CategoryController::class, 'restoreMany']);
            Route::post('{category}/restore', [CategoryController::class, 'restore'])->withTrashed();
            Route::delete('{category}/force', [CategoryController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('categories', CategoryController::class);

        // -------------------------------------------------------------------------
        // Brands (enhanced with media library)
        // -------------------------------------------------------------------------

        Route::prefix('brands')->group(function (): void {
            Route::delete('bulk', [BrandController::class, 'destroyMany']);
            Route::post('bulk-restore', [BrandController::class, 'restoreMany']);
            Route::post('{brand}/restore', [BrandController::class, 'restore'])->withTrashed();
            Route::delete('{brand}/force', [BrandController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('brands', BrandController::class);

        // -------------------------------------------------------------------------
        // Product Collections
        // -------------------------------------------------------------------------

        Route::apiResource('product-collections', ProductCollectionController::class);
        Route::prefix('product-collections')->group(function (): void {
            Route::post('{product_collection}/products/{product}', [ProductCollectionController::class, 'attachProduct']);
            Route::delete('{product_collection}/products/{product}', [ProductCollectionController::class, 'detachProduct']);
            Route::put('{product_collection}/products/reorder', [ProductCollectionController::class, 'reorderProducts']);
        });
