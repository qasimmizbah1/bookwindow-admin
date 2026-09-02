<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get menu items by menu name with proper hierarchical order
     *
     * @param string $menuName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMenuItems($menuName)
    {
        $menus = Menu::all();

        // Get all menu items ordered by _lft to maintain tree structure
        $allItems = MenuItem::select('id', 'name', 'url', 'menu_id', 'parent_id', '_lft', '_rgt')
            ->whereIn('menu_id', $menus->pluck('id'))
            ->orderBy('_lft', 'asc')  // Critical: order by _lft for proper hierarchy
            ->get();

        // Build the tree structure maintaining the order
        $buildTree = function($items, $parentId = null) use (&$buildTree) {
            $result = [];
            foreach ($items as $item) {
                if ($item->parent_id == $parentId) {
                    $children = $buildTree($items, $item->id);
                    $node = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'url' => $item->url,
                        'menu_id' => $item->menu_id,
                        'parent_id' => $item->parent_id,
                    ];
                    if (count($children) > 0) {
                        $node['children'] = $children;
                    }
                    $result[] = $node;
                }
            }
            return $result;
        };

        // Build the tree starting from root items
        $tree = $buildTree($allItems, null);

        // Filter by menu_id
        $menu1Items = collect($tree)->where('menu_id', 1)->values();
        $menu2Items = collect($tree)->where('menu_id', 2)->values();

        return response()->json([
            'header' => $menu2Items,
            'footer' => $menu1Items
        ]);
    }
}