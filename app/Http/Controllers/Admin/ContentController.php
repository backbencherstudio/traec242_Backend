<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqStoreRequest;
use App\Models\Content;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;

class ContentController extends Controller
{


    /**
     * Return content and other data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function home_index()
    {
        $content = Content::pluck('value', 'key');

        $data = [
            'content' => $content,
            'other_data' => [
                'total_user' => User::where('type', 0)->count(),
                'total_provider' => User::where('type', 2)->count(),
                'avg_rating' => 3.3
            ]
        ];
        return $this->sendResponse($data);
    }
    /**
     * Update content values.
     *
     * @param Request $request
     * @return JsonResponse
     */


    public function home_update(Request $request)
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




    /**
     * Fetch all faqs from the database.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function faq_index()
    {
        $content = Faq::get();

        return $this->sendResponse($content);
    }


    /**
     * Store a newly created faq in storage.
     *
     * @param  \App\Http\Requests\FaqStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function faq_store(FaqStoreRequest $request)
    {
        $validated = $request->validated();

        $content = Faq::create([
            'question' => $validated['question'],
            'answer' =>  $validated['answer']
        ]);

        return $this->sendResponse($content);
    }



    /**
     * Delete a faq from the database.
     *
     * @param  \App\Http\Requests\FaqStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function faq_delete(Faq $faq)
    {
        $faq->delete();

        return $this->sendResponse([], 'Faq deleted successfully.');
    }



    public function privacy_index()
    {
        $privacy = PrivacyPolicy::first();

        return $this->sendResponse($privacy);
    }

    public function privacy_update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $privacy = PrivacyPolicy::first() ?? new PrivacyPolicy();

        $privacy->title = $request->title;
        $privacy->description = $request->description;
        $privacy->save();

        return $this->sendResponse($privacy, 'Privacy Policy updated successfully.');
    }
}
