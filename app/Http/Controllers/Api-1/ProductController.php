<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::with('category')->get());
    }

    public function show($slug)
    {
        //return response()->json(Product::with('category')->findOrFail($id));
        $product = Product::with('category')->where('slug', $slug)->firstOrFail();
        return response()->json($product);
    }

        public function productsByCategorySlug($slug)
        {
        $products = \App\Models\Product::with('category')
        ->whereHas('category', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->get();

        return response()->json($products);
        }
}
