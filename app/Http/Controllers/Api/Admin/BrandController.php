<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

#[Group('admin-brand', weight: 4)]
class BrandController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function index(): JsonResponse
    {
        $brands = Brand::latest()->get();

        return response()->json([
            'status' => true,
            'data' => BrandResource::collection($brands),
        ], 200);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->fileUploadService->upload($request->file('image'), 'uploads/brand');
        }

        $brand = Brand::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'image' => $imagePath,
            'slug' => Str::slug($request->name, '-'),
        ]);

        return response()->json([
            'status' => 'success',
            'brand' => new BrandResource($brand),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $brand = Brand::find($id);
        if (! $brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        return response()->json(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        if ($request->hasFile('image')) {
            $this->fileUploadService->delete($brand->image);
            $brand->image = $this->fileUploadService->upload($request->file('image'), 'uploads/brand');
        }

        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->status = $request->status;
        $brand->slug = Str::slug($request->name, '-');
        $brand->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Brand updated successfully!',
            'brand' => new BrandResource($brand),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $brand = Brand::find($id);
        if (! $brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $this->fileUploadService->delete($brand->image);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully']);
    }
}
