<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductManagementDeleteController extends Controller
{
    /**
     * Remove the specified product from storage.
     */
    public function __invoke(Product $product)
    {
        try {
            $product->delete();

            return redirect()
                ->route('product-management.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Product delete failed', [
                'product_id' => $product->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
}
