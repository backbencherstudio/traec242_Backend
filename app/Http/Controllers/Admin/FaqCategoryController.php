<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaqCategoryController extends Controller
{
    public function index()
    {
        $categories = FaqCategory::where('status', true)
            ->orderBy('order_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Faq_Category fetched Successfull!',
            'data' => $categories,

        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:faq_categories,name',
            'order_number' => 'nullable|integer',
        ]);

        $category = FaqCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'order_number' => $request->order_number ?? 0,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faq_Category Created Successfull!',
            'data' => $category,
        ], 201);
    }

    public function edit($id)
    {
        $category = FaqCategory::find($id);

        if (! $category) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(['success' => true, 'data' => $category], 200);
    }

    public function update(Request $request, $id)
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

        return response()->json(['success' => true, 'message' => 'Faq_Category Updated Successfull!', 'data' => $category]);
    }

    public function destroy($id)
    {
        $category = FaqCategory::find($id);

        if (! $category) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Faq_Category Deleted Successfull!']);
    }
}
