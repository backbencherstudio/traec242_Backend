<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{

    public function index()
    {
        $services = Service::where('user_id', auth()->id())->with(['category', 'pricings'])->latest()->get();

        $data = [
            'services' => ServiceResource::collection($services)
        ];

        return $this->sendResponse($data);
    }

    public function store(StoreServiceRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $imagePaths = [];

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        $imagePaths[] = Storage::disk('public')->put('services', $file);
                    }
                }

                $service = Service::create([
                    'title' => $request->title,
                    'user_id' => auth()->id(),
                    'category_id' => $request->category_id,
                    'location' => $request->location,
                    'description' => $request->description,
                    'image' => $imagePaths,
                ]);

                foreach ($request->pricings as $pricingData) {
                    $service->pricings()->create($pricingData);
                }

                return response()->json([
                    'message' => 'Service created successfully',
                    'data' => new ServiceResource($service->load('pricings'))
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create service'], 500);
        }
    }


    public function show($id)
    {
        $service = Service::with(['category', 'pricings'])->findOrFail($id);
        return new ServiceResource($service);
    }
}
