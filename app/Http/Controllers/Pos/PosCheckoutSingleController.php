<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosCheckoutSingleController extends Controller
{
    /**
     * Checkout a single product immediately (create transaction for that product).
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $product = Product::lockForUpdate()->findOrFail($data['product_id']);

        if ($product->current_stock < $data['quantity']) {
            return redirect()->back()->with('alert', [
                'type' => 'error',
                'message' => 'Not enough stock for this product.',
            ]);
        }

        DB::beginTransaction();
        try {
            $total = (float) $product->price * $data['quantity'];

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'total' => $total,
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'price' => $product->price,
                'total' => $total,
            ]);

            // Update product stock and total_sold
            $product->current_stock = $product->current_stock - $data['quantity'];
            $product->total_sold = $product->total_sold + $data['quantity'];
            $product->save();

            DB::commit();

            return redirect('/pos-terminal')->with('alert', [
                'type' => 'success',
                'message' => 'Checkout successful.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('POS single checkout failed', [
                'user_id' => $user->id ?? null,
                'product_id' => $data['product_id'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('production') ? null : $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('alert', [
                'type' => 'error',
                'message' => 'Checkout failed.',
            ]);
        }
    }
}
