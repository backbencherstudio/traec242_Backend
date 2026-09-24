<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderPayment;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserManagementService $userManagementService,
        protected FileUploadService $fileUploadService
    ) {}

    public function clients(Request $request): JsonResponse
    {
        $users = $this->userManagementService->getPaginatedClients(
            $request->search,
            $request->status,
            $request->period,
            (int) ($request->per_page ?? 10)
        );

        $data = $users->getCollection()->map(fn ($user): array => [
            'id' => $user->id,
            'image_url' => $user->image ? asset($user->image) : null,
            'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => trim(
                ($user->address ?? '').', '.
                    ($user->city ?? '').', '.
                    ($user->state ?? '').' '.
                    ($user->zip_code ?? '')
            ),
            'joined' => $user->created_at?->format('m/d/Y'),
            'total_orders' => (int) ($user->total_orders ?? 0),
            'total_spent' => '$'.number_format((float) ($user->total_spent ?? 0), 2),
            'status' => $user->status ? 'Active' : 'Inactive',
            'is_verified' => (bool) $user->is_verified,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function showDetails($id, Request $request): JsonResponse
    {
        $user = User::where('type', 0)->findOrFail($id);
        $period = $request->period;

        $ordersQuery = Order::where('user_id', $user->id);
        $this->applyPeriodFilter($ordersQuery, $period);
        $totalOrders = $ordersQuery->count();

        $spentQuery = ProviderPayment::join('orders', 'provider_payments.order_id', '=', 'orders.id')
            ->where('provider_payments.user_id', $user->id)
            ->where('orders.status', 'completed')
            ->where('provider_payments.status', 'successful');
        $this->applyPeriodFilter($spentQuery, $period, 'provider_payments.created_at');
        $totalSpent = $spentQuery->sum('provider_payments.amount');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
                'email' => $user->email,
                'phone' => $user->phone,
                'image_url' => $user->image ? asset($user->image) : null,
                'address' => trim(
                    ($user->address ?? '').', '.
                        ($user->city ?? '').', '.
                        ($user->state ?? '').' '.
                        ($user->zip_code ?? '')
                ),
                'status' => $user->status ? 'Active' : 'Inactive',
                'is_verified' => (bool) $user->is_verified,
                'joined' => $user->created_at?->format('m/d/Y'),
                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_spent' => '$'.number_format((float) $totalSpent, 2),
                ],
            ],
        ]);
    }

    public function sellers(Request $request): JsonResponse
    {
        $users = $this->userManagementService->getPaginatedSellers(
            $request->search,
            $request->status,
            $request->period,
            (int) ($request->per_page ?? 10)
        );

        $data = $users->getCollection()->map(fn ($user): array => [
            'id' => $user->id,
            'image_url' => $user->image ? asset($user->image) : null,
            'name' => trim(($user->name ?? '').' '.($user->last_name ?? '')),
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => trim(
                ($user->address ?? '').', '.
                    ($user->city ?? '').', '.
                    ($user->state ?? '').' '.
                    ($user->zip_code ?? '')
            ),
            'joined' => $user->created_at?->format('m/d/Y'),
            'total_products' => (int) ($user->total_services ?? 0),
            'status' => $user->status ? 'Active' : 'Inactive',
            'is_verified' => (bool) $user->is_verified,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function changeStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => [
                'id' => $user->id,
                'status' => $user->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function deleteUser($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->fileUploadService->delete($user->image);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    protected function applyPeriodFilter($query, ?string $period, string $column = 'created_at'): void
    {
        if ($period === 'monthly') {
            $query->whereMonth($column, now()->month)
                ->whereYear($column, now()->year);
        } elseif ($period === 'weekly') {
            $query->whereBetween($column, [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        } elseif ($period === 'yearly') {
            $query->whereYear($column, now()->year);
        }
    }
}
