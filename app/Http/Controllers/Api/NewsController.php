<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = News::get();
        return response()->json($pages);
    }
    public function newsBySlug(string $slug)
    {
    $page = News::where('slug', $slug)->firstOrFail();
    return response()->json($page);
    }
     public function show(string $slug)
    {
        $page = News::where('slug', $slug)->firstOrFail();
        return response()->json($page);
    }
}