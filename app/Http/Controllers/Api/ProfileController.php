<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function providerProfile()
    {
        $user = auth()->user();

        $completedOrders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'image' => $user->image,
                'name' => trim($user->name.' '.$user->last_name),
                'is_verified' => $user->is_verified ? 'Verified' : null,
                'location' => trim(($user->city ?? '').($user->state ? ', '.$user->state : '')),
                'member_since' => 'Member since '.$user->created_at?->format('Y'),
                'about_me' => $user->bio,
                'completed_orders' => $completedOrders,
                'languages' => $user->languages ?? [],
                'joined' => 'Joined '.$user->created_at->format('M Y'),
                'email' => $user->email,
            ],
        ]);
    }

    public function updateProviderProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string',
            'languages' => 'sometimes|array',
            'languages.*' => 'string',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}
