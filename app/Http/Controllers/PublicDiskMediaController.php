<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicDiskMediaController extends Controller
{
    public function show(string $path): StreamedResponse|BinaryFileResponse
    {
        $normalizedPath = $this->normalizePath($path);

        if (Storage::disk('public')->exists($normalizedPath)) {
            $mimeType = Storage::disk('public')->mimeType($normalizedPath) ?: 'application/octet-stream';

            return Storage::disk('public')->response($normalizedPath, null, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $publicFile = public_path($normalizedPath);

        if (File::exists($publicFile)) {
            return response()->file($publicFile, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $storageFile = storage_path($normalizedPath);

        if (File::exists($storageFile)) {
            return response()->file($storageFile, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(404);
    }

    private function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }
}
