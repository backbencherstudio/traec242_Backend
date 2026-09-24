<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AllServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): JsonResponse
    {
        $services = Service::where('status', 1)
            ->with(['category', 'pricings', 'user', 'faqs'])

            ->when($request->query('search'), fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'))

            ->when($request->query('categories'), function ($query, $categories) {
                if (! is_array($categories)) {
                    $categories = explode(',', str_replace(['[', ']', ' '], '', $categories));
                }

                return $query->whereIn('category_id', $categories);
            })

            ->when($request->query('category'), fn ($query, $categoryName) => $query->whereHas('category', function ($q) use ($categoryName): void {
                $q->where('name', 'like', '%'.$categoryName.'%');
            }))

            ->when($request->query('location'), fn ($query, $location) => $query->where('location', 'like', '%'.$location.'%'))

            ->when($request->query('max_price'), fn ($query, $maxPrice) => $query->whereHas('pricings', function ($q) use ($maxPrice): void {
                $q->where('price', '<=', $maxPrice);
            }))

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->sendResponse(ServiceResource::collection($services));
    }

    /**
     * Display the specified service.
     */
    public function show($id): JsonResponse
    {
        $service = Service::where('status', 1)
            ->with(['category', 'pricings', 'user', 'faqs', 'reviews.user'])
            ->findOrFail($id);

        return $this->sendResponse(ServiceResource::make($service));
    }
}
