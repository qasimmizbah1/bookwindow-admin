<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VendorRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8',

            'store_name'            => 'required|string|max:255',
            'contact_person_name'   => 'required|string|max:255',
            'support_phone'         => ['required', 'regex:/^[6-9]\d{9}$/'],

            'website'               => 'nullable|url',
            'vendor_logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'warehouse_address'     => 'required|string',
            'city'                  => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s.\'-]+$/'],
            'state'                 => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s.\'-]+$/'],
            'pincode'               => 'required|digits:6',

            'pan_number'            => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'gstin'                 => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[1-9A-Za-z]{1}Z[0-9A-Za-z]{1}$/'],
            'isbn_license'          => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9-]+$/'],

            'bank_name'             => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s.&\'\-]+$/'],
            'account_holder_name'   => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s.\'-]+$/'],
            'bank_account_number'   => ['required', 'string', 'regex:/^\d{9,18}$/'],
            'ifsc_code'             => ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'upi_id'                => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-_]{2,64}@[a-zA-Z]{2,32}$/'],
        ], [
            'support_phone.regex'        => 'Support phone number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.',
            'city.regex'                 => 'City name must contain only letters and spaces (no special characters).',
            'state.regex'                => 'State name must contain only letters and spaces (no special characters).',
            'pincode.digits'             => 'Pincode must be exactly 6 digits.',
            'pan_number.regex'           => 'Invalid PAN format (e.g. ABCDE1234F). 10 alphanumeric characters required.',
            'gstin.regex'                => 'Invalid GSTIN format (e.g. 08AAAAA0000A1Z5) - no special characters allowed.',
            'isbn_license.regex'         => 'Publisher code / ISBN must contain only letters, numbers, and hyphens (no special characters).',
            'bank_name.regex'            => 'Bank name must contain only letters and spaces (no special characters).',
            'account_holder_name.regex'  => 'Account holder name must contain only letters and spaces.',
            'bank_account_number.regex'  => 'Bank account number must be between 9 and 18 digits (numbers only, no special characters).',
            'ifsc_code.regex'            => 'Invalid IFSC code format (e.g. SBIN0001234). 11 characters required with no special characters.',
            'upi_id.regex'               => 'Invalid UPI ID format (e.g. store@upi).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'vendor',
                'is_active' => 0,
            ]);

            $logo = null;

            if ($request->hasFile('vendor_logo')) {
                $logo = $request->file('vendor_logo')
                    ->store('vendors', 'public');
            }

            $vendor = Vendor::create([
                'user_id'               => $user->id,
                'vendor_name'           => $request->store_name,
                'contact_person'        => $request->contact_person_name,
                'vendor_phone'          => $request->support_phone,
                'vendor_website'        => $request->website,
                'vendor_logo'           => $logo,

                'vendor_address'        => $request->warehouse_address,
                'city'                  => $request->city,
                'state'                 => $request->state,
                'pincode'               => $request->pincode,

                'pan_number'            => $request->pan_number,
                'gst_number'            => $request->gstin,
                'isbn_number'           => $request->isbn_license,

                'bank_name'             => $request->bank_name,
                'account_holder_name'   => $request->account_holder_name,
                'account_number'        => $request->bank_account_number,
                'ifsc_code'             => $request->ifsc_code,
                'upi_id'                => $request->upi_id,

                'approval_status'       => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendor registration submitted successfully! Your account will be reviewed shortly.',
                'data' => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'store_name' => $vendor->vendor_name,
                    'status'     => 'pending',
                ]
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}