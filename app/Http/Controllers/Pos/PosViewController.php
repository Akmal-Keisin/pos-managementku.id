<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosViewController extends Controller
{
    /**
     * Display the POS terminal with paginated products.
     */
    public function index(Request $request): Response
    {
        $query = Product::query()->whereNull('deleted_at');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->get('search') . '%');
        }

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('pos/Index', [
            'products' => $products,
        ]);
    }
}
