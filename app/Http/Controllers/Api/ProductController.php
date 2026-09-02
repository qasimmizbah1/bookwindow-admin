<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    // public function index()
    // {
    //     return response()->json(Product::with('category')->get());
        
    // }
    public function index()
{
    $products = Product::all();

    return response()->json($products);
}


    public function show($slug)
    {
        //$product = Product::with(['category','production'])->where('slug', $slug)->firstOrFail();

        $product = Product::where('slug', $slug)->firstOrFail();



        $product = Product::with(['production'])
        ->where('slug', $slug)
        ->firstOrFail();
        
        // $categoryIds = json_decode($product->category_id, true);

        // $product->categories = Category::whereIn('id', $categoryIds ?: [])->get();

            $relatedProducts = Product::where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
            $categoryIds = $product->category_id;

            foreach ($categoryIds as $category) {
            $query->orWhereJsonContains('category_id', (string) $category);
            }
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        //Product According to sales
        // $bought = Product::select('products.*', DB::raw('COUNT(*) as total'))
        // ->join('order_items', 'products.id', '=', 'order_items.product_id')
        // ->whereIn('order_items.order_id', function ($query) use ($product) {
        //     $query->select('order_id')
        //             ->from('order_items')
        //             ->where('product_id', $product->id);
        // })
        // ->where('products.id', '!=', $product->id)
        // ->groupBy('products.id')
        // ->orderByDesc('total')
        // ->limit(5)
        // ->get();
        $excludeIds = $relatedProducts->pluck('id')->push($product->id);

        $bought = Product::whereNotIn('id', $excludeIds)
        ->inRandomOrder()
        ->limit(3)
        ->get();

        
        return response()->json([
        'product' => $product,
        'related_products' => $relatedProducts,
        'bought_together' => $bought
    ]);

        
    }

    public function productsByCategorySlug($slug)
    {
        try{

            $category = Category::where('slug', $slug)->first();
            if (!$category) {
            return response()->json([
            'error' => 'Category not found'
            ], 404);
            }


            $products = \App\Models\Product::where(function ($query) use ($category) {
            //$query->where('category_id', $category->id)
            $query->whereJsonContains('category_id', (string) $category->id);
            })
            ->select(
            'id',
            'production_id',
            'category_id',
            'name',
            'slug',
            'sku',
            'image',
            'description',
            'mrp',
            'price'
            )
            ->get();

            // Get unique Sub Category IDs
            $subCategoryIds = $products->pluck('sub_category_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

            // Get unique Production IDs
            $productionIds = $products->pluck('production_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

            // Sub Categories
            // $subcategories = DB::table('categories')
            // ->select('id', 'name', 'slug', 'parent_id')
            // ->whereIn('id', $subCategoryIds)
            // ->get();

            $categoryIds = $products->flatMap(function ($product) {

            // category_id is stored as JSON
            $ids = is_array($product->category_id)
            ? $product->category_id
            : json_decode($product->category_id, true);

            return $ids ?: [];
            })->unique()->values()->toArray();

            // Get unique Sub Category IDs
            $subcategories = DB::table('categories')
            ->select('id', 'name')
            ->whereIn('id', $categoryIds)
            ->get();

            // Productions
            $productions = DB::table('productions')
            ->select('id', 'name')
            ->where('is_visible', 1)
            ->whereIn('id', $productionIds)
            ->get();

            $data = [
            'parent-cateogry'=> $category,
            'category'   => $subcategories,
            'products'   => $products,
            'production' => $productions,
            'seo'  => [
            'meta_title' => $category->meta_tag_title,
            'meta_description' => $category->meta_tag_description,
            'meta_keywords' => $category->meta_tag_keywords,
            'image' => $category->cat_image,
            ]
            ];



return response()->json($data);

        //return response()->json($products);
    }
        catch (\Exception $e) {
            logger()->error('Registration error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
