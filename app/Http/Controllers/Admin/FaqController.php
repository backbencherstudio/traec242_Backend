<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {

        $faqs = Faq::with('faq_category')
            ->orderBy('order_number', 'asc')
            ->get();

        return response()->json([
            'status' => true,
             'message' => 'Faq fetched Successfull!',
            'data' => $faqs,

        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'faq_category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'status' => 'nullable|boolean',
            'order_number' => 'nullable|integer',
        ]);

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
            'data' => $faq,
        ], 201);
    }

    public function edit($id)
    {
        $faq = Faq::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $faq,
        ]);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $request->validate([
            'faq_category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'status' => 'nullable|boolean',
            'order_number' => 'nullable|integer',
        ]);

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
            'data' => $faq,
        ]);
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'status' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }
}
