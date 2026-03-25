<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;

class SliderController extends Controller
{

    public function index()
    {
        $sliders = Slider::all();
        return response()->json($sliders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|boolean',
            'order_number' => 'required|integer'
        ]);

        $slider = new Slider();
        $slider->title = $request->title;
        $slider->description = $request->description;
        $slider->status = $request->status;
        $slider->order_number = $request->order_number;

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = uniqid() . '.' . $file->getClientOriginalName();
            $file->move(public_path('uploads/sliders'), $filename);
            $slider->thumbnail = 'uploads/sliders/' . $filename;
        }

        $slider->save();

        return response()->json([
            'status' => 'Created slider successfully!',
            'data' => $slider
        ], 201);
    }


    public function edit($id)
    {
        $slider = Slider::find($id);
        if (!$slider) {
            return response()->json(['message' => 'Slider not found'], 404);
        }
        return response()->json($slider);
    }


    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'nullable|boolean',
            'order_number' => 'required|integer'
        ]);

        $slider->title = $request->title;
        $slider->description = $request->description;
        $slider->status = $request->status;
        $slider->order_number = $request->order_number;

        if ($request->hasFile('thumbnail')) {
            if ($slider->thumbnail && file_exists(public_path($slider->thumbnail))) {
                unlink(public_path($slider->thumbnail));
            }
            $file = $request->file('thumbnail');
            $filename = uniqid() . '.' . $file->getClientOriginalName();
            $file->move(public_path('uploads/sliders'), $filename);
            $slider->thumbnail = 'uploads/sliders/' . $filename;
        }
        $slider->save();

        return response()->json([
            'status' => 'Slider updated successfully!',
            'data' => $slider
        ]);
    }

    public function destroy($id)
    {
        $slider = Slider::find($id);

        if (! $slider) {
            return response()->json(['message' => 'slider not found'], 404);
        }

        if ($slider->image && file_exists(public_path($slider->image))) {
            unlink(public_path($slider->image));
        }

        $slider->delete();

        return response()->json(['message' => 'slider deleted successfully']);
    }
}
