<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactPage;


class ContactPageController extends Controller
{
    public function index()
    {
        $ContactPage = ContactPage::first();
        
        if (!$ContactPage) {
            return response()->json(['message' => 'Contact page not configured'], 404);
        }
        
            
        return response()->json([
            
            'contact_detials' => $ContactPage,
                        
        ]);
    }
}


