<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriberMail;

class SubscriberController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::all();

        return response()->json([
            'status' => true,
            'data' => $subscribers,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscriber = Subscriber::create([
            'email' => $request->email,
        ]);

        Mail::to($request->email)->queue(new SubscriberMail($request->email));

        return response()->json([
            'status' => true,
            'message' => 'Subscriber added successfully!',
            'data' => $subscriber,
        ], 201);
    }
}
