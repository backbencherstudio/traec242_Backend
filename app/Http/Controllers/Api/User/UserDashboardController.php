<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatConversationResource;
use App\Http\Resources\DashboardRecentMessageResource;
use App\Http\Resources\UserDashboardRecentOrderResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $summary = $this->dashboardService->getUserSummary($userId);

        return $this->sendResponse($summary);
    }

    public function recentOrders(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $orders = $this->dashboardService->getUserRecentOrders($userId);

        return $this->sendResponse(UserDashboardRecentOrderResource::collection($orders));
    }

    public function recentActivity(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $activities = $this->dashboardService->getUserRecentActivity($userId);

        return $this->sendResponse($activities);
    }

    public function recentMessages(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $messages = $this->dashboardService->getUserRecentMessages($userId);

        return $this->sendResponse(DashboardRecentMessageResource::collection($messages));
    }

    public function chat(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $conversations = $this->dashboardService->getUserConversations($userId, $request->input('search'));

        return $this->sendResponse(ChatConversationResource::collection($conversations));
    }
}
