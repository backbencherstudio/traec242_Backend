<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ProviderRegisterController extends Controller
{
    /**
     * Store a newly created provider in storage.
     *
     * @param ProviderRegisterRequest $request
     * @return JsonResponse
     */
    public function store(ProviderRegisterRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code'],
            'password' => $validated['password'],
            'services_id' => $validated['services_id'],
            'type' => 2,
            'status' => 1
        ]);

        return $this->sendResponse([], 'Provider registered successfully');
    }
}
