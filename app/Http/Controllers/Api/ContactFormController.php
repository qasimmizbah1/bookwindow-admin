<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use App\Mail\TutorFormSubmitted;
use App\Mail\VendorRegistrationSubmitted;
use App\Mail\ProductRequestSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactFormController extends Controller
{
    public function send(ContactFormRequest $request)
    {
        $validated = $request->validated();

        // Send email to admin
        Mail::to(env('ADMIN_EMAIL'))
            ->send(new ContactFormMail(
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['subject'],
                $validated['emailMessage'],
            ));

        return response()->json([
            'message' => 'Your message has been sent successfully!'
        ], 200);
    }

    /**
     * Handle Tutor Form Submission
     */
    public function submitTutorForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'role' => 'required',
            'locality' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            Mail::to('qasimmizbah@gmail.com')->send(new TutorFormSubmitted($request->all()));
            return response()->json(['message' => 'Tutor form submitted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Vendor Registration Form Submission
     */
    public function submitVendorForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            Mail::to('qasimmizbah@gmail.com')->send(new VendorRegistrationSubmitted($request->all()));
            return response()->json(['message' => 'Vendor registration submitted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Product Request Form Submission
     */
    public function submitProductRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'request' => 'required|string',
            'remark' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            
            // Handle image upload if present
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('product_requests', 'public');
                $data['image_path'] = $imagePath;
            }

            Mail::to('qasimmizbah@gmail.com')->send(new ProductRequestSubmitted($data));
            return response()->json(['message' => 'Product request submitted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }
}