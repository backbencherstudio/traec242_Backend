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

        $query = User::where('type', '0')
            ->withCount([
                'orders as total_order',

                'orders as complete_order' => function ($q) {
                    $q->where('status', 'completed');
                },

                'orders as pending_order' => function ($q) {
                    $q->whereIn('status', ['pending', 'confirmed']);
                }
            ]);

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
        $summary = Order::count();
        $processing = Order::whereIn('status', ['pending', 'confirmed'])->count();
        $delivered = Order::where('status', 'completed')->count();
        $activeOrder = Order::where('status', 'confirmed')
            ->whereHas('providerPayments', function ($q) {
                $q->where('status', 'successful');
            })
            ->count();

        $data = $users->map(function ($user) {

            $totalSpent = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful')
                ->sum('provider_payments.amount');

            return [
                'customer_info' => [
                    'id' => $user->id,
                    'image' => $user->image,
                    'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'total_order' => $user->total_order,
                    'complete_order' => $user->complete_order,
                    'pending_order' => $user->pending_order,
                    'total_spent' => '$' . number_format($totalSpent, 2),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => [
                'total_orders' => $summary,
                'processing' => $processing,
                'active_orders' => $activeOrder,
                'delivered' => $delivered,
            ],
            'data' => $data
        ]);
    }
}
