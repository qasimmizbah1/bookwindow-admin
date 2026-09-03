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
            'support_phone'         => 'required|digits_between:10,15',

            'website'               => 'nullable|url',
            'vendor_logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'warehouse_address'     => 'required|string',
            'city'                  => 'required|string|max:255',
            'state'                 => 'required|string|max:255',
            'pincode'               => 'required|string|max:10',

            'pan_number'            => 'required|string|max:20',
            'gstin'                 => 'nullable|string|max:30',
            'isbn_license'          => 'nullable|string|max:100',

            'bank_name'             => 'required|string|max:255',
            'account_holder_name'   => 'required|string|max:255',
            'bank_account_number'   => 'required|string|max:50',
            'ifsc_code'             => 'required|string|max:20',
            'upi_id'                => 'nullable|string|max:255',
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