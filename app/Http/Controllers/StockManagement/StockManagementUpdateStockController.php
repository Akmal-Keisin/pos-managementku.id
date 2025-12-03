<?php

namespace App\Http\Controllers\StockManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockManagement\StockManagementUpdateStockRequest;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockManagementUpdateStockController extends Controller
{
    /**
     * Update product stock.
     */
    public function __invoke(StockManagementUpdateStockRequest $request)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product);
            $quantity = $request->update_stock;

            // Update product stock based on type
            if ($request->type === 'increase') {
                $product->current_stock += $quantity;
            } else {
                // Ensure we don't go negative
                if ($product->current_stock < $quantity) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('alert', [
                            'type' => 'error',
                            'message' => 'Insufficient stock.',
                            'description' => 'Current stock: ' . $product->current_stock,
                        ]);
                }
                $product->current_stock -= $quantity;
            }

            $product->save();

            // Create stock history
            StockHistory::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => $request->type,
                'quantity' => $quantity,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()
                ->route('stock-management.index')
                ->with('alert', [
                    'type' => 'success',
                    'message' => 'Stock updated successfully.',
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock update failed', [
                'user_id' => Auth::id(),
                'product_id' => $request->product ?? null,
                'type' => $request->type ?? null,
                'quantity' => $request->update_stock ?? null,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('alert', [
                    'type' => 'error',
                    'message' => 'Failed to update stock.',
                    'description' => $e->getMessage(),
                ]);
        }
    }
}
