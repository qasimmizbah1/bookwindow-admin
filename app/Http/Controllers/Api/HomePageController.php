<?php
// app/Http/Controllers/Api/HomePageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Production;

class HomePageController extends Controller
{
    private function parseIds($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = explode(',', $raw);
            }
        }
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $raw), fn ($id) => $id > 0));
    }

    public function index()
    {
        $homePage = HomePage::first();
        
        if (!$homePage) {
            return response()->json(['message' => 'Home page not configured'], 404);
        }

        // 1. Popular Categories (ordered)
        $popularIds = $this->parseIds($homePage->popular_category);
        $popularCategories = !empty($popularIds)
            ? Category::whereIn('id', $popularIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $popularIds) . ')')
                ->get(['id', 'cat_image', 'name', 'slug'])
            : collect();

        // 2. Best Seller Products (ordered)
        $bestSellerIds = $this->parseIds($homePage->best_sellers);
        $bestSellers = !empty($bestSellerIds)
            ? Product::visibleToCustomers()
                ->whereIn('id', $bestSellerIds)
                ->select([
                    'id', 'name', 'slug', 'sku', 'model', 'author',
                    'image', 'gallery', 'description', 'mrp', 'price',
                    'book_language', 'quantity', 'production_id'
                ])
                ->with(['production:id,name,slug'])
                ->orderByRaw('FIELD(id, ' . implode(',', $bestSellerIds) . ')')
                ->get()
            : collect();

        // 3. Mock Test Categories (ordered)
        $mockIds = $this->parseIds($homePage->mock_test_category);
        $mockCategories = !empty($mockIds)
            ? Category::whereIn('id', $mockIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $mockIds) . ')')
                ->get(['id', 'cat_image', 'name', 'slug'])
            : collect();

        // 4. Hobby Categories (ordered)
        $hobbyIds = $this->parseIds($homePage->hobby_category);
        $hobbyCategories = !empty($hobbyIds)
            ? Category::whereIn('id', $hobbyIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $hobbyIds) . ')')
                ->get(['id', 'cat_image', 'name', 'slug'])
            : collect();

        // 5. Publications (ordered)
        $publicationIds = $this->parseIds($homePage->publication);
        $publications = !empty($publicationIds)
            ? Production::whereIn('id', $publicationIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $publicationIds) . ')')
                ->get(['id', 'name', 'publication_img', 'slug'])
            : collect();
            
        return response()->json([
            'slider_section' => $homePage->slider_section,
            'mobile_slider_section' => $homePage->mslider_section,
            'popular_section' => [
                'popular_title' => $homePage->popular_title,
                'popular_subtitle' => $homePage->popular_subtitle,
                'popular_category' => $popularCategories,
            ],
            'best_sellers_section' => [
                'title' => $homePage->best_sellers_title ?: 'Best Sellers',
                'subtitle' => $homePage->best_sellers_subtitle ?: '',
                'products' => $bestSellers,
            ],
            'mock_test_section' => [
                'mock_subtitle' => $homePage->mock_subtitle,
                'mock_test_category' => $mockCategories,
            ],
            'hobby_section' => [
                'hobby_subtitle' => $homePage->hobby_subtitle,
                'hobby_category' => $hobbyCategories,
            ],
            'publication_section' => [
                'publications_subtitle' => $homePage->publications_subtitle,
                'publication' => $publications,
            ],
            'banner' => [
                'banner_button_url' => $homePage->banner_button_url,
                'images' => $homePage->banner_images,
            ],
            'category_section' => [
                'cat_sec_title' => $homePage->cat_sec_title,
                'cat_sec_description' => $homePage->cat_sec_description,
                'category_sections' => $homePage->category_sections,
            ],
            'seo' => [
                'meta_title' => $homePage->meta_tag_title,
                'meta_description' => $homePage->meta_tag_description,
                'meta_keywords' => $homePage->meta_tag_keywords,
            ],
        ]);
    }
}