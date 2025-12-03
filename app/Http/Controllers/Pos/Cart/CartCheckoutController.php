<?php

namespace App\Http\Controllers\Pos\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartCheckoutController extends Controller
{
    /**
     * Checkout all items in the user's cart.
     */
    public function __invoke(Request $request, $userId)
    {
        $user = $request->user();

        if ($user->id != (int)$userId) {
            // only allow checkout for same user in this implementation
            abort(403);
        }

        $items = CartItem::with('product')->where('user_id', $userId)->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('alert', [
                'type' => 'error',
                'message' => 'Cart is empty.',
            ]);
        }

        DB::beginTransaction();
        try {
            $total = '0.00';
            foreach ($items as $item) {
                $lineTotal = bcmul((string)$item->price, (string)$item->quantity, 2);
                $total = bcadd($total, $lineTotal, 2);
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'total' => $total,
            ]);

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->current_stock < $item->quantity) {
                    DB::rollBack();
                    return redirect()->back()->with('alert', [
                        'type' => 'error',
                        'message' => "Not enough stock for {$item->product->name}.",
                    ]);
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => bcmul((string)$item->price, (string)$item->quantity, 2),
                ]);

                $product->current_stock = $product->current_stock - $item->quantity;
                $product->total_sold = $product->total_sold + $item->quantity;
                $product->save();
            }

            // clear cart after success
            CartItem::where('user_id', $userId)->delete();

            DB::commit();

            return redirect('/pos-terminal')->with('alert', [
                'type' => 'success',
                'message' => 'Checkout successful.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('POS cart checkout failed', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return redirect()->back()->with('alert', [
                'type' => 'error',
                'message' => 'Checkout failed.',
            ]);
        }
    }
}
