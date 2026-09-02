<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Production;
use App\Models\CmsPost;
use App\Models\CmsCategory;
use App\Models\CmsPage;


class SitemapController extends Controller
{
    public function index()
    {
        return response()->json([
            'pages' => $this->sitemapItems(CmsPage::class),
            'products' => $this->sitemapItems(Product::class, '/products'),
            'product_categories' => $this->sitemapItems(Category::class, '/category'),
            'posts' => $this->sitemapItems(CmsPost::class, '/blog'),
            'post_categories' => $this->sitemapItems(CmsCategory::class, '/blog/category'),
            'publications' => $this->sitemapItems(Production::class, '/publisher'),
        ]);
    }

    private function sitemapItems($model, $prefix = '')
{
    return $model::select('slug', 'updated_at')
        ->get()
        ->map(fn ($item) => [
            'url' => $item->slug,
            'updated_at' => $item->updated_at->format('Y-m-d H:i P'),
        ]);
}
}