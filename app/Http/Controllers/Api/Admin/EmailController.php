<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

#[Group('admin-email', weight: 4)]
class EmailController extends Controller
{
    public function sendEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Mail::to($request->email)->send(new TestMail($request->all()));

        return $this->sendResponse([], 'Mail sent successfully!');
    }
}
