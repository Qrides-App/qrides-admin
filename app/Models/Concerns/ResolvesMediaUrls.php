<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait ResolvesMediaUrls
{
    protected function decorateMedia(?Media $file, array $conversions = ['thumb', 'preview']): ?Media
    {
        if (! $file) {
            return null;
        }

        $file->url = $this->resolveMediaUrl($file);

        foreach ($conversions as $conversion) {
            $file->{$conversion === 'thumb' ? 'thumbnail' : $conversion} = $this->resolveMediaUrl($file, $conversion);
        }

        return $file;
    }

    protected function decorateMediaCollection($files, array $conversions = ['thumb', 'preview'])
    {
        $files->each(function (Media $file) use ($conversions) {
            $this->decorateMedia($file, $conversions);
        });

        return $files;
    }

    protected function resolveMediaUrl(Media $media, ?string $conversion = null): string
    {
        $disk = $media->disk ?: 'public';
        $relativePath = $this->resolveMediaRelativePath($media, $conversion);

        if ($relativePath !== null) {
            return Storage::disk($disk)->url($relativePath);
        }

        return $conversion ? $media->getUrl($conversion) : $media->getUrl();
    }

    protected function resolveMediaRelativePath(Media $media, ?string $conversion = null): ?string
    {
        $disk = $media->disk ?: 'public';
        $directory = (string) $media->id;

        if ($conversion === null) {
            $expected = trim($directory.'/'.$media->file_name, '/');
            if (Storage::disk($disk)->exists($expected)) {
                return $expected;
            }

            foreach (Storage::disk($disk)->files($directory) as $path) {
                if (! str_contains($path, '/conversions/')) {
                    return $path;
                }
            }

            return null;
        }

        $conversionDirectory = trim($directory.'/conversions', '/');
        if (! Storage::disk($disk)->exists($conversionDirectory)) {
            return null;
        }

        foreach (Storage::disk($disk)->files($conversionDirectory) as $path) {
            if (str_contains(basename($path), '-'.$conversion.'.')) {
                return $path;
            }
        }

        return null;
    }
}
