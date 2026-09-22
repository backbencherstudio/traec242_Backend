<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload an uploaded file to the given directory on public disk.
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Delete a file path from public disk or legacy public_path if it exists.
     */
    public function delete(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        $fullPublicPath = public_path($path);
        if (file_exists($fullPublicPath) && ! is_dir($fullPublicPath)) {
            return @unlink($fullPublicPath);
        }

        return false;
    }

    /**
     * Delete multiple files.
     *
     * @param  array<string>  $paths
     */
    public function deleteMultiple(array $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }
}
