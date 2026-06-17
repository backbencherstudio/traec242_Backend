<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AllServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $services = Service::where('status', 1)
            ->with(['category', 'pricings', 'user', 'faqs'])

            ->when($request->query('search'), function ($query, $search) {
                return $query->where('title', 'like', '%'.$search.'%');
            })

            ->when($request->query('categories'), function ($query, $categories) {
                if (! is_array($categories)) {
                    $categories = explode(',', str_replace(['[', ']', ' '], '', $categories));
                }

                return $query->whereIn('category_id', $categories);
            })

            ->when($request->query('category'), function ($query, $categoryName) {
                return $query->whereHas('category', function ($q) use ($categoryName) {
                    $q->where('name', 'like', '%'.$categoryName.'%');
                });
            })

            ->when($request->query('location'), function ($query, $location) {
                return $query->where('location', 'like', '%'.$location.'%');
            })

            ->when($request->query('max_price'), function ($query, $maxPrice) {
                return $query->whereHas('pricings', function ($q) use ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                });
            })

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->sendResponse(ServiceResource::collection($services));
    }

    /**
     * Display the specified service.
     */
    public function show($id)
    {
        $service = Service::where('status', 1)
            ->with(['category', 'pricings', 'user', 'faqs'])
            ->findOrFail($id);

        return $this->sendResponse(ServiceResource::make($service));
    }
}
