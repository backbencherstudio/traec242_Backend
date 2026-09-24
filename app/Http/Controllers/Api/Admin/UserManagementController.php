<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserManagementClientDetailResource;
use App\Http\Resources\UserManagementClientResource;
use App\Http\Resources\UserManagementSellerResource;
use App\Models\User;
use App\Services\UserManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('admin-user-management', weight: 4)]
class UserManagementController extends Controller
{
    public function __construct(
        protected UserManagementService $userManagementService
    ) {}

    public function clients(Request $request): JsonResponse
    {
        $users = $this->userManagementService->getPaginatedClients(
            $request->search,
            $request->status,
            $request->period,
            (int) ($request->per_page ?? 10)
        );

        return $this->sendResponse(UserManagementClientResource::collection($users));
    }

    public function showDetails($id, Request $request): JsonResponse
    {
        $user = $this->userManagementService->getClientDetails((int) $id, $request->period);

        if (! $user instanceof User) {
            return $this->sendError('User not found', [], 404);
        }

        return $this->sendResponse(new UserManagementClientDetailResource($user));
    }

    public function sellers(Request $request): JsonResponse
    {
        $users = $this->userManagementService->getPaginatedSellers(
            $request->search,
            $request->status,
            $request->period,
            (int) ($request->per_page ?? 10)
        );

        return $this->sendResponse(UserManagementSellerResource::collection($users));
    }

    public function changeStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $user = $this->userManagementService->updateUserStatus((int) $id, (int) $request->status);

        if (! $user instanceof User) {
            return $this->sendError('User not found', [], 404);
        }

        return $this->sendResponse([
            'id' => $user->id,
            'status' => $user->status ? 'Active' : 'Inactive',
        ], 'User status updated successfully.');
    }

    public function deleteUser($id): JsonResponse
    {
        $deleted = $this->userManagementService->deleteUser((int) $id);

        if (! $deleted) {
            return $this->sendError('User not found', [], 404);
        }

        return $this->sendResponse([], 'User deleted successfully.');
    }
}
