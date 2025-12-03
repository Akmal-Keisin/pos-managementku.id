<?php

namespace App\Http\Controllers\Pos\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;

class CartClearController extends Controller
{
    /**
     * Clear all items from the user's cart.
     */
    public function __invoke($userId)
    {
        CartItem::where('user_id', $userId)->delete();

        return redirect()->back()->with('success', 'Cart cleared.');
    }
}
