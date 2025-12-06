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
                ->with('alert', [
                    'type' => 'success',
                    'message' => 'Product deleted successfully.',
                ]);
        } catch (\Exception $e) {
            Log::error('Product delete failed', [
                'product_id' => $product->id ?? null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('production') ? null : $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('alert', [
                    'type' => 'error',
                    'message' => 'Failed to delete product.',
                    'description' => $e->getMessage(),
                ]);
        }
    }
}
