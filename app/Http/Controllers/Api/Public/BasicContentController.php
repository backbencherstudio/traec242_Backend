<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\Review;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('public-basic-content', weight: 1)]
class BasicContentController extends Controller
{
    public function home_response(): JsonResponse
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

    public function faq(): JsonResponse
    {
        $content = Faq::all();

        return $this->sendResponse($content);
    }

    public function privacy(): JsonResponse
    {
        $content = PrivacyPolicy::first();

        return $this->sendResponse($content);
    }
}
