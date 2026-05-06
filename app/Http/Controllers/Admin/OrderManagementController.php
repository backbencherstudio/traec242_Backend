<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $status = $request->status;

        $query = User::where('type', '0');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if (!is_null($status)) {
            $query->where('status', $status);
        }

        $users = $query->get();

        $data = $users->map(function ($user) {

            $totalOrders = Order::where('user_id', $user->id)->count();
            $completeOrders = Order::where('user_id', $user->id)
                ->where('status', 'completed')->count();
            $pendingOrders = Order::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed'])->count();

            $totalSpent = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful')
                ->sum('provider_payments.amount');

            return [
                'id' => $user->id,
                'image' => $user->image,
                'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
                'email' => $user->email,
                'total_order' => $totalOrders,
                'complete_order' => $completeOrders,
                'pending_order' => $pendingOrders,
                'total_spent' => '$' . number_format($totalSpent, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
