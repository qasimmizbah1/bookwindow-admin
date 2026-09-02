<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsPost;
use Illuminate\Http\Request;

class CmsPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = CmsPost::where('is_active', true)->get();
        return response()->json($pages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_pages',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $page = CmsPost::create($validated);

        return response()->json($page, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $page = CmsPost::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $page = CmsPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:cms_pages,slug,'.$page->id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $page->update($validated);

        return response()->json($page);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = CmsPost::findOrFail($id);
        $page->delete();

        return response()->json(null, 204);
    }
        public function showBySlug(string $slug)
        {
        $page = CmsPost::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($page);
        }
}