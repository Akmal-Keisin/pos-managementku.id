<?php

namespace App\Http\Controllers\Pos\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;

class CartDeleteController extends Controller
{
	/**
	 * Remove a specific item from the cart.
	 */
	public function __invoke($userId, CartItem $cartItem)
	{
		if ($cartItem->user_id != $userId) {
			abort(403);
		}

		$cartItem->delete();

		return redirect()->back()->with('alert', [
			'type' => 'success',
			'message' => 'Item removed.',
		]);
	}
}
