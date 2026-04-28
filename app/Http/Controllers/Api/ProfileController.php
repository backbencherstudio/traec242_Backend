<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;

class ProfileController extends Controller
{
    public function providerProfile($id)
    {
        $user = User::findOrFail($id);

        $completedOrders = Order::where('user_id', $id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'id' => $user->id,

            'name' => trim($user->name . ' ' . $user->last_name),
            'email' => $user->email,
            'phone' => $user->phone,
            'image' => $user->image,

            'bio' => $user->bio,
            'languages' => $user->languages
                ? explode(',', $user->languages)
                : [],

            'is_verified' => (bool) $user->is_verified,
            'status' => $user->status,
            'type' => $user->type,

            'location' => trim(
                ($user->city ?? '') .
                    ($user->state ? ', ' . $user->state : '')
            ),

            'member_since' => $user->created_at
                ? $user->created_at->format('Y')
                : null,

            'completed_orders' => $completedOrders,

            'provider_status' => $user->provider_status,
        ]);
    }
}
