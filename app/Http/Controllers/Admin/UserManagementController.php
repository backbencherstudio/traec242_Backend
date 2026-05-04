<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;

class UserManagementController extends Controller
{
    public function clients()
    {
        $users = User::where('type', '0')->get();

        $data = $users->map(function ($user) {

            $totalOrders = Order::where('user_id', $user->id)->count();

            $totalSpent = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful')
                ->sum('provider_payments.amount');

            return [
                'image' => $user->image,
                'name' => trim($user->name . ' ' . $user->last_name),
                'email' => $user->email,
                'orders' => $totalOrders,
                'total_spent' => '$' . number_format($totalSpent, 2),
                'joined' => $user->created_at->format('m/d/Y'),
                'status' => $user->status ? 'Active' : 'Inactive',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
