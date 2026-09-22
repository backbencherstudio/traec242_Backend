<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function index(): JsonResponse
    {
        $subcategories = Subcategory::with('category')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => SubcategoryResource::collection($subcategories),
        ]);
    }

    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->fileUploadService->upload($request->file('image'), 'uploads/subcategory');
        }

        $subcategory = Subcategory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'status' => $request->status,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new SubcategoryResource($subcategory->load('category')),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $subcategory = Subcategory::with('category')->findOrFail($id);

        return response()->json(new SubcategoryResource($subcategory));
    }

    public function update(UpdateSubcategoryRequest $request, $id): JsonResponse
    {
        $subcategory = Subcategory::findOrFail($id);

        $imagePath = $subcategory->image;
        if ($request->hasFile('image')) {
            $this->fileUploadService->delete($subcategory->image);
            $imagePath = $this->fileUploadService->upload($request->file('image'), 'uploads/subcategory');
        }

        $subcategory->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'status' => $request->status,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new SubcategoryResource($subcategory->fresh('category')),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $subcategory = Subcategory::findOrFail($id);
        $this->fileUploadService->delete($subcategory->image);
        $subcategory->delete();

        return response()->json([
            'message' => 'Subcategory deleted successfully',
        ]);
    }
}
