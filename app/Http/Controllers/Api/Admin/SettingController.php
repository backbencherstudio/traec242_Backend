<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    public function index(): JsonResponse
    {
        $setting = Setting::firstOrCreate([], []);

        return response()->json([
            'success' => true,
            'data' => new SettingResource($setting),
        ]);
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $setting = Setting::firstOrCreate([], []);

        $imageFields = ['site_logo', 'admin_logo', 'favicon', 'seo_image'];
        $data = $request->only($setting->getFillable());

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $this->fileUploadService->delete($setting->$field);
                $data[$field] = $this->fileUploadService->upload($request->file($field), 'uploads/settings');
            }
        }

        $setting->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => new SettingResource($setting),
        ]);
    }
}
