<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Mail\NewServiceMail;
use App\Models\Service;
use App\Models\Subscriber;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ServiceController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function index(): JsonResponse
    {
        $services = Service::where('user_id', auth()->id())
            ->with(['category', 'pricings', 'faqs'])
            ->latest()
            ->get();

        return $this->sendResponse([
            'services' => ServiceResource::collection($services),
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request): JsonResponse {
                $imagePaths = [];

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        $imagePaths[] = $this->fileUploadService->upload($file, 'services');
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

                Subscriber::chunk(50, function ($subscribers) use ($service): void {
                    foreach ($subscribers as $subscriber) {
                        Mail::to($subscriber->email)
                            ->queue(new NewServiceMail($service));
                    }
                });

                return $this->sendResponse(
                    ServiceResource::make($service->load(['pricings', 'faqs'])),
                    'Service created successfully'
                );
            });
        } catch (\Throwable $e) {
            return $this->sendError('Failed to create service: '.$e->getMessage(), [], 500);
        }
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        if ((int) $service->user_id !== (int) auth()->id()) {
            return $this->sendError('You are not authorized to update this service.', [], 403);
        }

        try {
            return DB::transaction(function () use ($request, $service): JsonResponse {
                $data = $request->only([
                    'title',
                    'category_id',
                    'location',
                    'description',
                ]);

                if ($request->hasFile('images')) {
                    if (! empty($service->image) && is_array($service->image)) {
                        $this->fileUploadService->deleteMultiple($service->image);
                    }

                    $imagePaths = [];
                    foreach ($request->file('images') as $file) {
                        $imagePaths[] = $this->fileUploadService->upload($file, 'services');
                    }

                    $data['image'] = $imagePaths;
                }

                $service->update($data);

                if ($request->has('pricings')) {
                    $service->pricings()->delete();

                    foreach ($request->pricings as $pricing) {
                        $service->pricings()->create([
                            'service_type' => $pricing['service_type'],
                            'duration' => $pricing['duration'] ?? null,
                            'price' => $pricing['price'],
                            'description' => $pricing['description'] ?? null,
                            'features' => $pricing['features'] ?? [],
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
                    ServiceResource::make($service->load(['pricings', 'faqs'])),
                    'Service updated successfully'
                );
            });
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $service = Service::where('user_id', auth()->id())
            ->with(['category', 'pricings', 'faqs'])
            ->findOrFail($id);

        return $this->sendResponse(ServiceResource::make($service));
    }
}
