<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('admin-category', weight: 4)]
class CategoryController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    #[Group('public-category', weight: 1)]
    public function index(): JsonResponse
    {
        $categories = Category::with('subcategories')->latest()->get();

        return response()->json([
            'status' => true,
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->fileUploadService->upload($request->file('image'), 'uploads/category');
        }

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => true,
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        if ($request->hasFile('image')) {
            $this->fileUploadService->delete($category->image);
            $category->image = $this->fileUploadService->upload($request->file('image'), 'uploads/category');
        }

        $category->name = $request->name;
        $category->description = $request->description;
        $category->status = $request->status;
        $category->save();

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully!',
            'category' => new CategoryResource($category),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        if ((int) auth()->user()->type !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this category.',
            ], 403);
        }

        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $this->fileUploadService->delete($category->image);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
