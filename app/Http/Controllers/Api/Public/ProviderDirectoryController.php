<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);

        $query = User::role('provider')->with(['subscriptions', 'plan']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function (Builder $qBuilder) use ($search): void {
                $qBuilder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $category = $request->get('category');

            $query->where(function (Builder $qb) use ($category): void {
                $qb->whereJsonContains('category_id', $category)
                    ->orWhereExists(function ($sub) use ($category): void {
                        $sub->select(DB::raw(1))
                            ->from('services')
                            ->whereColumn('services.user_id', 'users.id')
                            ->where('services.category_id', $category);
                    });
            });
        }

        $providers = $query->latest()->paginate($perPage);

        return $this->sendResponse(UserResource::collection($providers));
    }

    public function show($id): JsonResponse
    {
        $provider = User::role('provider')
            ->with(['subscriptions', 'plan'])
            ->find($id);

        if (! $provider) {
            return $this->sendError('Provider not found', [], 404);
        }

        $services = Service::where('user_id', $provider->id)
            ->with(['category', 'pricings'])
            ->get();

        $categories = collect();
        if (! empty($provider->category_id) && is_array($provider->category_id)) {
            $categories = Category::whereIn('id', $provider->category_id)->get();
        }

        return $this->sendResponse([
            'provider' => UserResource::make($provider),
            'categories' => CategoryResource::collection($categories),
            'services' => ServiceResource::collection($services),
        ]);
    }
}
