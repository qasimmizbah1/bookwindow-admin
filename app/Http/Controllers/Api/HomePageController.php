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
    public function index()
    {
        $homePage = HomePage::first();
        
        if (!$homePage) {
            return response()->json(['message' => 'Home page not configured'], 404);
        }
        
            
        return response()->json([
            'slider_section' => $homePage->slider_section,
            'mobile_slider_section' => $homePage->mslider_section,
            'popular_section' => [
                'popular_title' => $homePage->popular_title,
                'popular_subtitle' => $homePage->popular_subtitle,
                'popular_category' => Category::whereIn('id', $homePage->popular_category)
            ->get(['cat_image', 'name', 'slug']),
            ],
            'mock_test_section' => [
                'mock_subtitle' => $homePage->mock_subtitle,
                'mock_test_category' => Category::whereIn('id', $homePage->mock_test_category)
            ->get(['cat_image', 'name', 'slug']),
            ],
            'hobby_section' => [
                'hobby_subtitle' => $homePage->hobby_subtitle,
                'hobby_category' => Category::whereIn('id', $homePage->hobby_category)
            ->get(['cat_image', 'name', 'slug']),
            ],
            'publication_section' => [
                'publications_subtitle' => $homePage->publications_subtitle,
                'publication' => Production::whereIn('id', $homePage->publication)
            ->get(['id', 'name', 'publication_img', 'slug']),
            ],
            'banner' => [
               
                'banner_button_url' => $homePage->banner_button_url,
                'images' => $homePage->banner_images,
               
            ],
            // 'banner' => [
            //     'banner_description' => $homePage->banner_description,
            //     'banner_button_title' => $homePage->banner_button_title,
            //     'banner_button_url' => $homePage->banner_button_url,
            //     'images' => $homePage->banner_images,
            //     'logo_img' => $homePage->banner_logo,
            // ],
            'category_section' => [
                'cat_sec_title' => $homePage->cat_sec_title,
                'cat_sec_description' => $homePage->cat_sec_description,
                'category_sections' => $homePage->category_sections,
            ],
            // 'category_tabs' => [
            //     'cat_tab_subtitle' => $homePage->cat_tab_subtitle,
            //     'cat_tab_title' => $homePage->cat_tab_title,
            //     'cat_tab_description' => $homePage->cat_tab_description,
            //     'cat_tabs' => Category::whereIn('id', $homePage->cat_tabs)
            // ->get(['id', 'name', 'slug'])
            // ->toArray(),
            // ],
            // 'testimonial_sections' => $homePage->testimonial_sections,
            // 'feature_sections' => [
            //     'feature_title' => $homePage->feature_title,
            //     'feature_description' => $homePage->feature_description,
            //     'feature_data' => $homePage->custom_sections,
            // ],
            'seo' => [
                'meta_title' => $homePage->meta_tag_title,
                'meta_description' => $homePage->meta_tag_description,
                'meta_keywords' => $homePage->meta_tag_keywords,
            ],

            
        ]);
    }
}