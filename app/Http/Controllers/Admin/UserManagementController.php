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

        if (! is_null($status)) {
            $query->where('status', $status);
        }

        if ($period == 'monthly') {

            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($period == 'weekly') {

            $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period == 'yearly') {

            $query->whereYear('created_at', now()->year);
        }

        $perPage = $request->per_page ?? 10;

        $users = $query->paginate($perPage);

        $data = $users->getCollection()->map(function ($user) use ($period) {

            $totalOrdersQuery = Order::where('user_id', $user->id);

            if ($period == 'monthly') {

                $totalOrdersQuery->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            } elseif ($period == 'weekly') {

                $totalOrdersQuery->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            } elseif ($period == 'yearly') {

                $totalOrdersQuery->whereYear('created_at', now()->year);
            }

            $totalOrders = $totalOrdersQuery->count();

            $totalSpentQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful');

            if ($period == 'monthly') {

                $totalSpentQuery->whereMonth('provider_payments.created_at', now()->month)
                    ->whereYear('provider_payments.created_at', now()->year);
            } elseif ($period == 'weekly') {

                $totalSpentQuery->whereBetween('provider_payments.created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            } elseif ($period == 'yearly') {

                $totalSpentQuery->whereYear('provider_payments.created_at', now()->year);
            }

            $totalSpent = $totalSpentQuery->sum('provider_payments.amount');

            return [
                'id' => $user->id,
                'image_url' => $user->image ? asset($user->image) : null,
                'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
                'email' => $user->email,
                'orders' => $totalOrders,
                'total_spent' => '$'.number_format($totalSpent, 2),
                'joined' => $user->created_at->format('m/d/Y'),
                'status' => $user->status ? 'Active' : 'Inactive',
            ];
        });

        $users->setCollection($data);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
        ]);
    }

    public function showDetails($id, Request $request)
    {
        $period = $request->period;

        $user = User::whereIn('type', [0, 2])
            ->where('id', $id)
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $ordersQuery = Order::where('user_id', $user->id);

        if ($period == 'monthly') {
            $ordersQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($period == 'weekly') {
            $ordersQuery->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period == 'yearly') {
            $ordersQuery->whereYear('created_at', now()->year);
        }

        $totalOrders = $ordersQuery->count();

        $spentQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
            ->where('provider_payments.user_id', $user->id)
            ->where('orders.status', 'completed')
            ->where('provider_payments.status', 'successful');

        if ($period == 'monthly') {
            $spentQuery->whereMonth('provider_payments.created_at', now()->month)
                ->whereYear('provider_payments.created_at', now()->year);
        } elseif ($period == 'weekly') {
            $spentQuery->whereBetween('provider_payments.created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period == 'yearly') {
            $spentQuery->whereYear('provider_payments.created_at', now()->year);
        }

        $totalSpent = $spentQuery->sum('provider_payments.amount');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image,
                'address' => trim(
                    ($user->address ?? '').', '.
                        ($user->city ?? '').', '.
                        ($user->state ?? '').' '.
                        ($user->zip_code ?? '')
                ),
                'status' => $user->status ? 'Active' : 'Inactive',
                'is_verified' => (bool) $user->is_verified,
                'joined' => $user->created_at->format('m/d/Y'),

                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_spent' => '$'.number_format($totalSpent, 2),
                ],

            ],
        ]);
    }

    public function sellers(Request $request)
    {
        $search = $request->search;
        $status = $request->status;
        $period = $request->period;

        $query = User::where('type', '2');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if (! is_null($status)) {
            $query->where('status', $status);
        }

        if ($period == 'monthly') {

            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($period == 'weekly') {

            $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period == 'yearly') {

            $query->whereYear('created_at', now()->year);
        }

        $perPage = $request->per_page ?? 10;

        $users = $query->paginate($perPage);

        $data = $users->getCollection()->map(function ($user) use ($period) {

            $productsQuery = Service::where('user_id', $user->id);

            if ($period == 'monthly') {

                $productsQuery->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            } elseif ($period == 'weekly') {

                $productsQuery->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            } elseif ($period == 'yearly') {

                $productsQuery->whereYear('created_at', now()->year);
            }

            $products = $productsQuery->count();

            $totalSalesQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
                ->where('provider_payments.user_id', $user->id)
                ->where('orders.status', 'completed')
                ->where('provider_payments.status', 'successful');

            if ($period == 'monthly') {

                $totalSalesQuery->whereMonth('provider_payments.created_at', now()->month)
                    ->whereYear('provider_payments.created_at', now()->year);
            } elseif ($period == 'weekly') {

                $totalSalesQuery->whereBetween('provider_payments.created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            } elseif ($period == 'yearly') {

                $totalSalesQuery->whereYear('provider_payments.created_at', now()->year);
            }

            $totalSales = $totalSalesQuery->sum('provider_payments.amount');

            return [
                'id' => $user->id,
                'image_url' => $user->image ? asset($user->image) : null,
                'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
                'email' => $user->email,
                'products' => $products,
                'total_sales' => '$'.number_format($totalSales, 2),
                'joined' => $user->created_at->format('m/d/Y'),
                'status' => $user->status ? 'Active' : 'Inactive',
            ];
        });

        $users->setCollection($data);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
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
            'status' => 'required|in:0,1',
        ]);

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => [
                'id' => $user->id,
                'status' => $user->status,
            ],
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already deleted',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'data' => [
                'id' => $user->id,
                'deleted_at' => $user->deleted_at,
            ],
        ]);
    }
}
