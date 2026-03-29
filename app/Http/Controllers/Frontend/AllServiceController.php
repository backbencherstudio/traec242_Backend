<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;

class AllServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::where('status', 1)
            ->with(['category', 'pricings', 'user'])

            ->when($request->query('search'), function ($query, $search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })

            ->when($request->query('categories'), function ($query, $categories) {
                if (!is_array($categories)) {
                    $categories = explode(',', str_replace(['[', ']', ' '], '', $categories));
                }
                return $query->whereIn('category_id', $categories);
            })

            ->when($request->query('category'), function ($query, $categoryName) {
                return $query->whereHas('category', function ($q) use ($categoryName) {
                    $q->where('name', 'like', '%' . $categoryName . '%');
                });
            })

            ->when($request->query('location'), function ($query, $location) {
                return $query->where('location', 'like', '%' . $location . '%');
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
}
