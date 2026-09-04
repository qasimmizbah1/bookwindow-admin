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
        $query = Product::visibleToCustomers();

        // 1. Keyword search (supports 'q', 'key', 'query')
        $searchTerm = $request->input('q', $request->input('key', $request->input('query')));
        if (!empty($searchTerm) && trim($searchTerm) !== '') {
            $rawTokens = preg_split('/[\s,+/\\-]+/', trim($searchTerm));
            $tokens = array_values(array_filter($rawTokens, function ($t) {
                return strlen(trim($t)) > 0;
            }));

            // Recognize common bookstore filler words and typos (e.g. 'reet book', 'reet buk' -> matches 'reet')
            $stopwords = [
                'book', 'books', 'buk', 'bukk', 'buks', 'bok', 'boks', 'boook', 'bk',
                'kitab', 'kitaben', 'kitb', 'katab', 'pustak', 'pustaken', 'pstk',
                'guide', 'gaid', 'gide', 'edition'
            ];
            $meaningfulTokens = array_values(array_filter($tokens, function ($t) use ($stopwords) {
                $lower = strtolower($t);
                if (in_array($lower, $stopwords)) return false;
                if (strlen($lower) >= 3 && levenshtein($lower, 'book') <= 1) return false;
                return true;
            }));

            $searchTokens = !empty($meaningfulTokens) ? $meaningfulTokens : $tokens;

            // LIKE Query behavior: match ANY token (e.g. 'reet buk' will match any product with 'reet')
            $query->where(function ($subQuery) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $subQuery->orWhere(function ($q) use ($token) {
                        $q->where('name', 'like', "%{$token}%")
                          ->orWhere('description', 'like', "%{$token}%")
                          ->orWhere('model', 'like', "%{$token}%")
                          ->orWhere('author', 'like', "%{$token}%")
                          ->orWhere('sku', 'like', "%{$token}%");
                    });
                }
            });
        }

        // 2. Publication filter (only apply if explicitly specified)
        if ($request->filled('publication')) {
            $query->where('production_id', $request->input('publication'));
        }

        // 3. Category filter
        if ($request->filled('category')) {
            $category = $request->input('category');
            $query->where(function ($q) use ($category) {
                $q->where('sub_category_id', $category)
                  ->orWhereJsonContains('category_id', (string) $category)
                  ->orWhereJsonContains('category_id', (int) $category);
            });
        }

        // 4. Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        // 5. Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'price', 'name', 'id'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, strtolower($sortDirection) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 6. Pagination
        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }
}