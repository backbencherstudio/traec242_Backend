<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function clients(Request $request)
    {
        $search = $request->search;
        $status = $request->status;
        $period = $request->period;

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

        if ($period == 'monthly') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        }

        $users = $query->get();

        $data = $users->map(function ($user) use ($period) {

            $totalOrdersQuery = Order::where('user_id', $user->id);

            if ($period == 'monthly') {
                $totalOrdersQuery->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }

            $totalOrders = $totalOrdersQuery->count();

            $totalSpentQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful');

            if ($period == 'monthly') {
                $totalSpentQuery->whereMonth('provider_payments.created_at', now()->month)
                    ->whereYear('provider_payments.created_at', now()->year);
            }

            $totalSpent = $totalSpentQuery->sum('provider_payments.amount');

            return [
                'id' => $user->id,
                'image' => $user->image,
                'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
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

    public function sellers(Request $request)
    {
        $search = $request->search;
        $status = $request->status;

        $query = User::where('type', '2');

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

            $products = Service::where('user_id', $user->id)->count();

            $totalSales = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful')
                ->sum('provider_payments.amount');

            return [
                'id' => $user->id,
                'image' => $user->image,
                'name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
                'email' => $user->email,
                'products' => $products,
                'total_sales' => '$' . number_format($totalSales, 2),
                'joined' => $user->created_at->format('m/d/Y'),
                'status' => $user->status ? 'Active' : 'Inactive',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        if (auth()->user()->type !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to change status.',
            ], 403);
        }
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => [
                'id' => $user->id,
                'status' => $user->status
            ]
        ]);
    }

    public function deleteUser($id)
    {
        if (auth()->user()->type !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete user.',
            ], 403);
        }

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already deleted'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'data' => [
                'id' => $user->id,
                'deleted_at' => $user->deleted_at,
            ]
        ]);
    }
}
