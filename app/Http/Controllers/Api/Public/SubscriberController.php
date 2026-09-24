<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriber\StoreSubscriberRequest;
use App\Http\Resources\SubscriberResource;
use App\Mail\SubscriberMail;
use App\Models\Subscriber;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

#[Group('public-subscriber', weight: 1)]
class SubscriberController extends Controller
{
    #[Group('admin-subscriber', weight: 4)]
    public function index(): JsonResponse
    {
        $subscribers = Subscriber::latest()->get();

        return response()->json([
            'status' => true,
            'data' => SubscriberResource::collection($subscribers),
        ]);
    }

    public function store(StoreSubscriberRequest $request): JsonResponse
    {
        $subscriber = Subscriber::create([
            'email' => $request->email,
        ]);

        Mail::to($request->email)->queue(new SubscriberMail($request->email));

        return response()->json([
            'status' => true,
            'message' => 'Subscriber added successfully!',
            'data' => new SubscriberResource($subscriber),
        ], 201);
    }
}
