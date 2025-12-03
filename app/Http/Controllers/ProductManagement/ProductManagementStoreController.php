<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductManagement\ProductManagementStoreRequest;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductManagementStoreController extends Controller
{
    /**
     * Store a newly created product in storage.
     */
    public function __invoke(ProductManagementStoreRequest $request)
    {
        try {
            $product = Product::create([
                'name' => $request->name,
                'sku' => $request->sku ?: null, // Will auto-generate if null
                'current_stock' => $request->starting_stock ?: 0,
                'price' => $request->price,
                'description' => $request->description ?: null,
                'image' => null,
            ]);

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $product->image = $path;
                $product->save();
            }

            // Create stock history if starting stock > 0
            if ($request->starting_stock && $request->starting_stock > 0) {
                StockHistory::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'increase',
                    'quantity' => $request->starting_stock,
                    'notes' => 'Initial stock',
                ]);
            }

            return redirect()
                ->route('product-management.index')
                ->with('alert', [
                    'type' => 'success',
                    'message' => 'Product created successfully.',
                ]);
        } catch (\Exception $e) {
            Log::error('Product create failed', [
                'user_id' => Auth::id(),
                'name' => $request->name ?? null,
                'sku' => $request->sku ?? null,
                'message' => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('alert', [
                    'type' => 'error',
                    'message' => 'Failed to create product.',
                    'description' => $e->getMessage(),
                ]);
        }
    }
}
