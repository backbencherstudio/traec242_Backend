<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProviderProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProviderProfileController extends Controller
{
    public function providerProfile(): JsonResponse
    {
        $user = auth()->user();

        $completedOrders = Order::whereHas('service', fn ($q) => $q->where('user_id', $user->id))
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
                'joined' => 'Joined '.$user->created_at?->format('M Y'),
                'email' => $user->email,
            ],
        ]);
    }

    public function updateProviderProfile(UpdateProviderProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}
