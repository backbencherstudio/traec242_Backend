<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;

class PromotionController extends Controller
{

    public function index()
    {
        $promotions = Promotion::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Promotion list',
            'data' => $promotions
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'discount' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|boolean',
        ]);

        $promotion = Promotion::create([
            'name' => $request->name,
            'discount' => $request->discount,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promotion created successfully',
            'data' => $promotion
        ], 201);
    }


    public function edit($id)
    {
        $promotion = Promotion::find($id);

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promotion
        ]);
    }


    public function update(Request $request, $id)
    {
        $promotion = Promotion::find($id);

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'discount' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|boolean',
        ]);

        $promotion->update([
            'name' => $request->name,
            'discount' => $request->discount,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promotion updated successfully',
            'data' => $promotion
        ]);
    }


    public function destroy($id)
    {
        $promotion = Promotion::find($id);

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        $promotion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promotion deleted successfully'
        ]);
    }


    public function activePromotions()
    {
        $today = now()->toDateString();

        $promotions = Promotion::where('status', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active promotions',
            'data' => $promotions
        ]);
    }
}
