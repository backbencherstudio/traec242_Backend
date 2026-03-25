<?php

namespace App\Http\Controllers;

use App\Events\TestEvent;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function sendNotification(Request $request)
    {

        $message = $request->input('message', 'Hello World!');
        event(new TestEvent($message));
        return response()->json([
            'status' => 'Message sent',
            'message' => $message
        ]);
    }
}
