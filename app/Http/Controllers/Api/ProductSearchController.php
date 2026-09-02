<?php
// app/Http/Controllers/Api/ProductSearchController.php
namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = Product::query();

        // Basic search
        if ($request->has('key') || $request->has('publication')) {
            $searchTerm = $request->input('key');
            $publication = $request->input('publication');
            
                $query->where(function($q) use ($searchTerm, $publication) {
                $q->where('production_id', 'like', "%{$publication}%")
                ->where(function($q2) use ($searchTerm) {
                $q2->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhere('sku', 'like', "%{$searchTerm}%");
                });
                });
        }

        // Category filter
        if ($request->has('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }
}