<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;

class ContentController extends Controller
{

    public function index()
    {
        $content = Content::pluck('value', 'key');
        return $this->sendResponse($content);
    }

    public function update(Request $request)
    {
        $allowedKeys = [
            'image_1',
            'image_2',
            'image_3',
            'heading',
            'sub_heading',
            'input_placeholder',
            'button_text'
        ];

        foreach ($allowedKeys as $key) {
            if ($request->hasFile($key)) {
                $record = Content::where('key', $key)->first();

                if ($record && $record->value) {
                    Storage::disk('public')->delete($record->value);
                }

                $path = $request->file($key)->store('uploads', 'public');

                Content::updateOrCreate(['key' => $key], ['value' => $path]);
            } elseif ($request->has($key)) {
                Content::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        $updatedContent = Content::pluck('value', 'key');
        return $this->sendResponse($updatedContent, 'Content updated successfully.');
    }
}
