<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\User;

class BasicContentController extends Controller
{
    public function home_response()
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

    public function faq()
    {
        $content = Faq::all();
        return $this->sendResponse($content);
    }

    public function privacy()
    {
        $content = PrivacyPolicy::first();
        return $this->sendResponse($content);
    }
}
