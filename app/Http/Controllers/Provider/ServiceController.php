<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Mail\NewServiceMail;
use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('user_id', auth()->id())->with(['category', 'pricings', 'faqs'])->latest()->get();

        $data = [
            'services' => ServiceResource::collection($services),
        ];

        return $this->sendResponse($data);
    }
    // public function index()
    // {
    //     $services = Service::where('user_id', auth()->id())->with(['category', 'pricings'])->latest()->get();

    //     $data = [
    //         'services' => ServiceResource::collection($services)
    //     ];

    //     return $this->sendResponse($data);
    // }

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

                foreach ($request->faqs ?? [] as $faqData) {
                    $service->faqs()->create($faqData);
                }

                Subscriber::chunk(50, function ($subscribers) use ($service) {
                    foreach ($subscribers as $subscriber) {
                        Mail::to($subscriber->email)
                            ->queue(new NewServiceMail($service));
                    }
                });

                return $this->sendResponse(ServiceResource::make($service->load(['pricings', 'faqs'])), 'Service created successfully');
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create service'], 500);
        }
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {

        try {
            return DB::transaction(function () use ($request, $service) {

                $data = $request->only([
                    'title',
                    'category_id',
                    'location',
                    'description'
                ]);

                if ($request->hasFile('images')) {

                    if (!empty($service->image)) {
                        foreach ($service->image as $image) {
                            Storage::disk('public')->delete($image);
                        }
                    }

                    $imagePaths = [];

                    foreach ($request->file('images') as $file) {
                        $imagePaths[] = Storage::disk('public')->put('services', $file);
                    }

                    $data['image'] = $imagePaths;
                }

                $service->update($data);

                if ($request->has('pricings')) {

                    $service->pricings()->delete();

                    foreach ($request->pricings as $pricing) {
                        $service->pricings()->create([
                            'service_type' => $pricing['service_type'],
                            'duration'     => $pricing['duration'] ?? null,
                            'price'        => $pricing['price'],
                            'description'  => $pricing['description'] ?? null,
                            'features'     => $pricing['features'] ?? [],
                        ]);
                    }
                }

                if ($request->has('faqs')) {
                    $service->faqs()->delete();

                    foreach ($request->faqs as $faq) {
                        $service->faqs()->create($faq);
                    }
                }

                return $this->sendResponse(
                    ServiceResource::make(
                        $service->load(['pricings', 'faqs'])
                    ),
                    'Service updated successfully'
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $service = Service::where('user_id', auth()->id())
            ->with(['category', 'pricings', 'faqs'])
            ->findOrFail($id);

        return $this->sendResponse(ServiceResource::make($service));
    }
}
