<?php

namespace App\Http\Controllers\Pos\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartViewController extends Controller
{
	/**
	 * Display the cart for the specified user.
	 */
	public function show($userId): Response
	{
		$cartItems = CartItem::with('product')->where('user_id', $userId)->get();

		return Inertia::render('pos/Cart', [
			'cartItems' => $cartItems,
		]);
	}
}
