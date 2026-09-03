<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

class PublicationController extends Controller
{
    public function index()
    {

        //return response()->json(Production::all());
         // $productions = DB::table('productions')
         //           ->join('products', 'productions.id', '=', 'production_id')
         //           ->select('productions.*', 'products.name as product_name', 'products.description as product_description')
         //           ->get();

        //$production = Production::with('products')->get();
        $production = Production::where('is_visible', 1)
    ->select('name', 'description', 'publication_img')
    ->get();


         if (!$production) {
        return response()->json([
            'status' => false,
            'message' => 'Production not found'
        ], 404);
    }

    return response()->json([
        'data' => [
            'production' => $production,
           
        ]
    ]);


        return response()->json($productions);
    }


public function productsBySlug($slug)
{
    try {

        // Get production by slug
        $production = DB::table('productions')
            ->select('id', 'name', 'slug', 'meta_tag_title', 'meta_tag_description', 'meta_tag_keywords', 'publication_img' )
            ->where('is_visible', 1)
            ->where('slug', $slug)
            ->first();

        if (!$production) {
            return response()->json([
                'status' => false,
                'message' => 'Production not found'
            ], 404);
        }

        // Get products of this production
        $products = \App\Models\Product::visibleToCustomers()
            ->where('production_id', $production->id)
            ->select(
                'id',
                'name',
                'slug',
                'sku',
                'image',
                'description',
                'mrp',
                'price',
                'category_id'
            )
            ->get();


            $categoryIds = $products->flatMap(function ($product) {

            // category_id is stored as JSON
            $ids = is_array($product->category_id)
            ? $product->category_id
            : json_decode($product->category_id, true);

            return $ids ?: [];
            })->unique()->values()->toArray();

            // Get unique Sub Category IDs
            $categories = DB::table('categories')
            ->select('id', 'name')
            ->whereIn('id', $categoryIds)
            ->get();

            return response()->json([
            'products'   => $products,
            'category'   => $categories,
            'seo'  => [
            'meta_title' => $production->meta_tag_title,
            'meta_description' => $production->meta_tag_description,
            'meta_keywords' => $production->meta_tag_keywords,
            'image' => $production->publication_img,
            ]
            ]);

    } catch (\Exception $e) {

        logger()->error('Products By Slug Error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => false,
            'error'  => $e->getMessage()
        ], 500);
    }
}


}

?>