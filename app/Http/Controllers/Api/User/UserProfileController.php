<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[Group('user-profile', weight: 2)]
class UserProfileController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->fileUploadService->delete($user->image);
            $data['image'] = $this->fileUploadService->upload($request->file('image'), 'uploads/profile');
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}
