<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqCategoryRequest;
use App\Http\Requests\Admin\UpdateFaqCategoryRequest;
use App\Http\Resources\FaqCategoryResource;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class FaqCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = FaqCategory::where('status', true)
            ->orderBy('order_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Faq_Category fetched Successfull!',
            'data' => FaqCategoryResource::collection($categories),
        ], 200);
    }

    public function store(StoreFaqCategoryRequest $request): JsonResponse
    {
        $category = FaqCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'order_number' => $request->order_number ?? 0,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faq_Category Created Successfull!',
            'data' => new FaqCategoryResource($category),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $category = FaqCategory::find($id);
        if (! $category) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(['success' => true, 'data' => new FaqCategoryResource($category)], 200);
    }

    public function update(UpdateFaqCategoryRequest $request, $id): JsonResponse
    {
        $category = FaqCategory::find($id);
        if (! $category) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $category->update([
            'name' => $request->name ?? $category->name,
            'slug' => $request->name ? Str::slug($request->name) : $category->slug,
            'order_number' => $request->order_number ?? $category->order_number,
            'status' => $request->status ?? $category->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faq_Category Updated Successfull!',
            'data' => new FaqCategoryResource($category),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $category = FaqCategory::find($id);
        if (! $category) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Faq_Category Deleted Successfull!']);
    }
}
