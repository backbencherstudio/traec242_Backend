<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqStoreRequest;
use App\Models\Content;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\Review;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function home_index(): JsonResponse
    {
        $content = Content::pluck('value', 'key');
        $avgRating = Review::avg('rating');

        $data = [
            'content' => $content,
            'other_data' => [
                'total_user' => User::role('user')->count(),
                'total_provider' => User::role('provider')->count(),
                'avg_rating' => $avgRating ? round((float) $avgRating, 1) : 5.0,
            ],
        ];

        return $this->sendResponse($data);
    }

    public function home_update(Request $request): JsonResponse
    {
        $allowedKeys = [
            'image_1',
            'image_2',
            'image_3',
            'heading',
            'sub_heading',
            'input_placeholder',
            'button_text',
        ];

        foreach ($allowedKeys as $key) {
            if ($request->hasFile($key)) {
                $record = Content::where('key', $key)->first();
                if ($record && $record->value) {
                    $this->fileUploadService->delete($record->value);
                }

                $path = $this->fileUploadService->upload($request->file($key), 'uploads');
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

    public function faq_index(): JsonResponse
    {
        $content = Faq::all();

        return $this->sendResponse($content);
    }

    public function faq_store(FaqStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $content = Faq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
        ]);

        return $this->sendResponse($content);
    }

    public function faq_delete(Faq $faq): JsonResponse
    {
        $faq->delete();

        return $this->sendResponse([], 'Faq deleted successfully.');
    }

    public function privacy_index(): JsonResponse
    {
        $privacy = PrivacyPolicy::first();

        return $this->sendResponse($privacy);
    }

    public function privacy_update(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $privacy = PrivacyPolicy::first() ?? new PrivacyPolicy;
        $privacy->title = $request->title;
        $privacy->description = $request->description;
        $privacy->save();

        return $this->sendResponse($privacy, 'Privacy Policy updated successfully.');
    }
}
