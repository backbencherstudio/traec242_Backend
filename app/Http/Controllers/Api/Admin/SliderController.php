<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSliderRequest;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function index(): JsonResponse
    {
        $sliders = Slider::orderBy('order_number', 'asc')->get();

        return response()->json([
            'status' => true,
            'data' => SliderResource::collection($sliders),
        ]);
    }

    public function store(StoreSliderRequest $request): JsonResponse
    {
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $this->fileUploadService->upload($request->file('thumbnail'), 'uploads/sliders');
        }

        $slider = Slider::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'order_number' => $request->order_number,
            'thumbnail' => $thumbnailPath,
        ]);

        return response()->json([
            'status' => 'Created slider successfully!',
            'data' => new SliderResource($slider),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $slider = Slider::find($id);
        if (! $slider) {
            return response()->json(['message' => 'Slider not found'], 404);
        }

        return response()->json(new SliderResource($slider));
    }

    public function update(UpdateSliderRequest $request, $id): JsonResponse
    {
        $slider = Slider::findOrFail($id);

        if ($request->hasFile('thumbnail')) {
            $this->fileUploadService->delete($slider->thumbnail);
            $slider->thumbnail = $this->fileUploadService->upload($request->file('thumbnail'), 'uploads/sliders');
        }

        $slider->title = $request->title;
        $slider->description = $request->description;
        if ($request->has('status')) {
            $slider->status = $request->status;
        }
        $slider->order_number = $request->order_number;
        $slider->save();

        return response()->json([
            'status' => 'Slider updated successfully!',
            'data' => new SliderResource($slider),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $slider = Slider::find($id);
        if (! $slider) {
            return response()->json(['message' => 'slider not found'], 404);
        }

        $this->fileUploadService->delete($slider->thumbnail);
        $slider->delete();

        return response()->json(['message' => 'slider deleted successfully']);
    }
}
