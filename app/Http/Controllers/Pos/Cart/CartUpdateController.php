<?php

namespace App\Http\Controllers\Pos\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartUpdateController extends Controller
{
	/**
	 * Update the quantity of a cart item.
	 */
	public function __invoke(Request $request, $userId, CartItem $cartItem)
	{
		$data = $request->validate([
			'quantity' => 'required|integer|min:1',
		]);

		if ($cartItem->user_id != $userId) {
			abort(403);
		}

		$cartItem->update(['quantity' => $data['quantity']]);

		return redirect()->back()->with('success', 'Cart updated.');
	}
}
