<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload and store a file to the specified directory.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string|null $disk
     * @return string
     */
    public function upload(UploadedFile $file, string $directory, ?string $disk = 'public'): string
    {
        // Generate a unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store the file
        $path = $file->storeAs($directory, $filename, $disk);
        
        return $path;
    }

    /**
     * Delete a file from storage.
     *
     * @param string|null $filePath
     * @param string|null $disk
     * @return bool
     */
    public function delete(?string $filePath, ?string $disk = 'public'): bool
    {
        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            return false;
        }

        return Storage::disk($disk)->delete($filePath);
    }

    /**
     * Generate a unique filename for the uploaded file.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        return $filename;
    }

    /**
     * Get the public URL for a stored file.
     *
     * @param string|null $filePath
     * @param string|null $disk
     * @return string|null
     */
    public function getUrl(?string $filePath, ?string $disk = 'public'): ?string
    {
        if (!$filePath) {
            return null;
        }

        // For public disk, use asset helper
        if ($disk === 'public') {
            return asset('storage/' . $filePath);
        }

        // For other disks, return the path as is (might need custom handling)
        return $filePath;
    }
}