<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProviderRegisterController extends Controller
{

    public function store(ProviderRegisterRequest $request)
    {

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $user = User::create([
                'name'        => $validated['name'],
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'],
                'address'     => $validated['address'],
                'city'        => $validated['city'],
                'state'       => $validated['state'],
                'zip_code'    => $validated['zip_code'],
                'password'    => Hash::make($validated['password']),
                'category_id' => $validated['category_id'],
                'type'        => 1,
                'status'      => 1,
            ]);

            DB::commit();

            return $this->sendResponse([], 'Provider registered successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Registration failed', ['error' => $e->getMessage()]);
        }
    }
}
