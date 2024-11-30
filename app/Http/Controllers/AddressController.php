<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function getAddress ($id)
    {   
        if (!Auth::check()) {
            return response()->json(['message' => 'Address not found or unauthorized'], 403);
        }

        $user = Auth::user();

        $address = Address::where('id', $id)
                          ->where('user_id', $user->id)
                          ->first();

        if (!$address) {
            return response()->json([
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'address' => $address
        ], 201);
    }

    public function getAddresses (Request $request) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);
        }

        $user = Auth::user();
        $addresses = $user->addresses;

        if ($addresses->isEmpty()) {
            return response()->json([
                'message' => 'No addresses found'
            ], 404);
        }

        $sortedAddresses = $addresses->sortByDesc('is_default');

        $formattedAddresses = $sortedAddresses->map(function ($address) {
            return [
                'id' => $address->id,
                'user_id' => $address->user_id,
                'customer_name' => $address->customer_name,
                'phone_number' => $address->phone_number,
                'province_id' => $address->province_id,
                'province_name' => $address->province_name,
                'district_id' => $address->district_id,
                'district_name' => $address->district_name,
                'ward_id' => $address->ward_id,
                'ward_name' => $address->ward_name,
                'address_detail' => $address->address_detail,
                'is_default' => $address->is_default
            ];
        })->values();

        return response()->json([   
            'addresses' => $formattedAddresses,
        ], 201);
    }

    public function changeAddress (Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'province_id' => 'required|string|max:255',
                'district_id' => 'required|string|max:255',
                'ward_id' => 'required|string|max:255',
                'address_detail' => 'required|string|max:255',
                'customer_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:255',
                'province_name' => 'required|string|max:255',
                'district_name' => 'required|string|max:255',
                'ward_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'request error'], 422);
            }

            $address = $user->addresses->where('id', $request->id)->first();

            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $address->update($request->all());

            return response()->json(['message' => 'change success',
                                    'request' => $request->all()], 200);
        }
    }

    public function createAddress (Request $request)
    {   
        if (Auth::check()) {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'province_id' => 'required|string|max:255',
                'district_id' => 'required|string|max:255',
                'ward_id' => 'required|string|max:255',
                'address_detail' => 'required|string|max:255',
                'customer_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:255',
                'province_name' => 'required|string|max:255',
                'district_name' => 'required|string|max:255',
                'ward_name' => 'required|string|max:255',
                'is_default' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'request error',
                ], 422);
            }

            $data = $request->all();

            if (!Address::where('user_id', $user->id)->exists()) {
                $data['is_default'] = true;
            }

            $data['user_id'] = $user->id;

            Address::create($data);

            return response()->json(['message' => 'created success'], 200);
        }
    }

    public function updateAddressDefault (Request $request) 
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);
        }

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid request data'], 422);
        }

        $address_id = $request->input('address_id');

        $address = Address::where('id', $address_id)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        Address::where('user_id', $user->id)->update(['is_default' => false]);

        $address->is_default = true;
        $address->save();

        return response()->json(['message' => 'Address updated successfully'], 200);
    }

    public function deleteAddress (Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'User not validate',
            ], 401);  
        }

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid request data'], 422);
        }
 
        $user = Auth::user();

        $address = Address::where('id', $request->address_id)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'delete success'], 200);
    }

    public function getAddressDefault(Request $request) 
    {
        if (!Auth::check()) { 
            return response()->json([ 'message' => 'User not validated', ], 401); 
        }

        $user = Auth::user();

        $addressDefault = $user->addresses->where('is_default', true)->first();

        if (!$addressDefault) {
            return response()->json([
                'message' => 'Default address not found',
            ], 404);
        }

        return response()->json([
            'address' => $addressDefault,
        ], 200);
    }
}
