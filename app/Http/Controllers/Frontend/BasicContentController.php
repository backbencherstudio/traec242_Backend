<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use Illuminate\Http\Request;

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
}
