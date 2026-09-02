<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Support\Facades\DB;


class WishlistController extends Controller
{

public function index()
{
    $user = auth('customer')->user();

    $wishlist = Wishlist::where('user_id', $user->id)->first();

    if (!$wishlist || empty($wishlist->products)) {
        return response()->json([
            'status' => true,
            'data' => []
        ]);
    }

    $products = Product::whereIn('id', $wishlist->products)
        ->select(
            'id',
            'name',
            'slug',
            'image',
            'description',
            'mrp',
            'price',
            'quantity',
            'production_id',
            'category_id'
        )
        ->get();

   
    $categoryIds = $products->flatMap(function ($product) {
        $ids = is_array($product->category_id)
            ? $product->category_id
            : json_decode($product->category_id, true);

        return $ids ?: [];
    })->unique()->values()->toArray();


    $categories = DB::table('categories')
        ->select('id', 'name')
        ->whereIn('id', $categoryIds)
        ->get()
        ->keyBy('id');


    $productionIds = $products->pluck('production_id')
        ->filter()
        ->unique()
        ->values()
        ->toArray();

    $productions = DB::table('productions')
        ->select('id', 'name')
        ->where('is_visible', 1)
        ->whereIn('id', $productionIds)
        ->get()
        ->keyBy('id');


        $products = $products->map(function ($product) use ($categories, $productions) {

        $categoryIds = is_array($product->category_id)
            ? $product->category_id
            : json_decode($product->category_id, true);

        
        $product->categories = collect($categoryIds)
            ->map(function ($id) use ($categories) {
                return $categories[$id]->name ?? null;
            })
            ->filter()
            ->first();

        
        $product->production = $productions[$product->production_id]->name ?? null;

        // Hide fields
        unset($product->production_id);
        unset($product->category_id);

        return $product;
    });

    return response()->json([
        'status' => true,
        'data' => $products
    ]);
}

public function wishlistid()
{
    $user = auth('customer')->user();

    $wishlist = Wishlist::where('user_id', $user->id)->first();

    return response()->json([
        'status' => true,
        'data' => $wishlist->products
    ]);
}



    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|integer'
    ]);

    $user = auth('customer')->user();

    $wishlist = Wishlist::firstOrCreate(
        ['user_id' => $user->id],
        ['products' => []]
    );

    $products = $wishlist->products ?? [];

    if (in_array($request->product_id, $products)) {
        return response()->json([
            'status' => false,
            'message' => 'Product is already in your wishlist.',
        ]);
    }

    if (!in_array($request->product_id, $products)) {
        $products[] = $request->product_id;
    }

    $wishlist->update([
        'products' => array_values($products)
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Wishlist updated successfully.',
        // 'data' => $wishlist
    ]);
}

public function destroy(Request $request)
{

    $request->validate([
            'product_id' => 'required|integer'
    ]);

    $product_id = $request->product_id;

    $user = auth('customer')->user();

    $wishlist = Wishlist::where('user_id',$user->id)->first();

    if(!$wishlist){

        return response()->json([
            'status'=>false,
            'message'=>'Wishlist not found.'
        ]);
    }

    $products = $wishlist->products ?? [];
      
    if (!in_array($product_id, $products)) {
        return response()->json([
            'status' => false,
            'message' => 'Product not found in wishlist.'
        ]);
    }

    $products = array_values(array_filter($products,function($id) use ($product_id){
        return $id != $product_id;
    }));

    $wishlist->update([
        'products'=>$products
    ]);

    return response()->json([
        'status'=>true,
        'message'=>'Product removed successfully.',
        // 'data'=>$wishlist
    ]);
}


}
