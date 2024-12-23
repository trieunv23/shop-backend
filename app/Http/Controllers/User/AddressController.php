<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct() {
        $this->middleware('auth.jwt');
        $this->middleware('modify.response');
    }

    public function showAddresses() 
    {
        $user = Auth::user();

        $addresses = $user->addresses;

        if ($addresses->isEmpty()) {
            return response()->json([
                'message' => 'No addresses found'
            ], 404);
        }

        $sortedAddresses = $addresses->sortByDesc('is_default')->values()->toArray();

        return response()->json([   
            'addresses' => $sortedAddresses,
        ], 201);
    }

    public function showAddress($id)
    {   
        $user = Auth::user();

        $address = $user->addresses->find($id);

        if (!$address) {
            return response()->json([
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'address' => $address,
        ], 201);
    }

    public function updateAddress(Request $request, $id)
    {
        $user = Auth::user();

        $rules = [
            'province' => 'integer',
            'district' => 'integer',
            'ward' => 'integer',
            'detail' => 'string|max:255',
            'name' => 'string|max:255',
            'phone_number' => 'string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) { 
            return response()->json([ 
                'status' => 'error', 
                'errors' => $validator->errors() 
            ], 422); 
        }

        $address = $user->addresses->find($id);

        if (!$address) {
            return response()->json([
                'message' => 'Address not found'
            ], 404);
        }

        $address->update($request->only(array_keys($rules)));

        return response()->json([
            'message' => 'Updated success'
        ], 200);
    }

    public function createAddress(Request $request)
    {   
        $user = Auth::user();

        $rules = [
            'province' => 'required|integer',
            'district' => 'required|integer',
            'ward' => 'required|integer',
            'detail' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([ 
                'status' => 'error', 
                'errors' => $validator->errors() 
            ], 422); 
        }

        if (!Address::where('user_id', $user->id)->exists()) {
            $request['is_default'] = true;
        }

        $request['user_id'] = $user->id;

        $address = Address::create($request->only(array_merge(array_keys($rules), ['user_id', 'is_default'])));

        return response()->json([
            'message' => 'Address created successfully',
        ], 201);
    }

    public function updateAddressDefault($id) 
    {
        $user = Auth::user();

        $address = Address::where('id', $id)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        if (!$address->is_default) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();
        }

        return response()->json(['message' => 'Address updated successfully'], 200);
    }

    public function deleteAddress($id)
    {
        $user = Auth::user();

        $address = Address::where('id', $id)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json([
                'message' => 'Address not found'
            ], 404);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ], 200);
    }

    public function showAddressDefault() 
    {
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
