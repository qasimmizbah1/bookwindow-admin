<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);

        $response = Http::withBasicAuth(
            'anystring',
            config('services.mailchimp.key')
        )->post(
            'https://'.config('services.mailchimp.server').'.api.mailchimp.com/3.0/lists/'.config('services.mailchimp.audience').'/members',
            [
                'email_address' => $email,
                'status' => 'subscribed',
            ]
        );

        if ($response->successful()) {

            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed.'
            ]);
        }

        return response()->json([
            'success' => false,
            'mailchimp' => $response->json(),
        ], 400);
    }
}