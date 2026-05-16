<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'zip_code' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string',
            'languages' => 'sometimes|array',
            'languages.*' => 'string',
        ];

        if ($user->type === 2) {
            $rules['category_id'] = 'sometimes|array';
            $rules['category_id.*'] = 'integer|exists:categories,id';
        }

        $data = $request->validate($rules);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.$image->getClientOriginalName();
            $image->move(public_path('uploads/profile'), $imageName);
            $data['image'] = 'uploads/profile/'.$imageName;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}
