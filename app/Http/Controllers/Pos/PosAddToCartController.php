<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosAddToCartController extends Controller
{
    /**
     * Add item to cart for the current authenticated user.
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $product = Product::findOrFail($data['product_id']);

        CartItem::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            ['quantity' => DB::raw('GREATEST(quantity + ' . ((int)$data['quantity']) . ', 1)'), 'price' => $product->price]
        );

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'message' => 'Product added to cart.',
        ]);
    }
}
