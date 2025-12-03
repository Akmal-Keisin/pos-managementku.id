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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->get('search') . '%')
                    ->orWhere('sku', 'like', '%' . $request->get('search') . '%');
            });
        }

        $perPage = $request->get('per_page', 20);
        $products = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('pos/Index', [
            'products' => $products,
        ]);
    }
}
