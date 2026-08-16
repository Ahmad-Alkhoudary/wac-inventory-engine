<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductController
 *
 * RESTful API controller managing catalog product state and cached inventory valuations.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of all products with cached stock quantity and valuation.
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::withCount(['stockTransactions' => function ($query) {
            $query->whereNull('deleted_at');
        }])->get();

        return ProductResource::collection($products);
    }
}
