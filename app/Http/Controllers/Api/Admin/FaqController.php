<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('admin-faq', weight: 4)]
class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $faqs = Faq::with('faq_category')
            ->orderBy('order_number', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Faq fetched Successfull!',
            'data' => FaqResource::collection($faqs),
        ]);
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = Faq::create([
            'faq_category_id' => $request->faq_category_id,
            'question' => $request->question,
            'answer' => $request->answer,
            'status' => $request->status ?? 1,
            'order_number' => $request->order_number ?? 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ created successfully',
            'data' => new FaqResource($faq->load('faq_category')),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $faq = Faq::with('faq_category')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => new FaqResource($faq),
        ]);
    }

    public function update(UpdateFaqRequest $request, $id): JsonResponse
    {
        $faq = Faq::findOrFail($id);

        $faq->update([
            'faq_category_id' => $request->faq_category_id,
            'question' => $request->question,
            'answer' => $request->answer,
            'status' => $request->status ?? $faq->status,
            'order_number' => $request->order_number ?? $faq->order_number,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ updated successfully',
            'data' => new FaqResource($faq->fresh('faq_category')),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'status' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }
}
