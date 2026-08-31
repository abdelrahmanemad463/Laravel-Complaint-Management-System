<?php

namespace App\Services\Visitors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class VisitorPhotoService
{
    public const MAX_ORIGINAL_SIZE = 20 * 1024 * 1024; // 20 MB

    /**
     * Validate and persist an evidence photo as a compressed image on the local
     * (private) disk. Returns the created photo record data.
     *
     * @return array{path:string,original_name:string,mime_type:string,original_size:int,compressed_size:int}
     */
    public function store(UploadedFile $file): array
    {
        if ($file->getSize() > self::MAX_ORIGINAL_SIZE) {
            throw new RuntimeException('The photo exceeds the maximum allowed size of 20 MB.');
        }

        $originalName = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $originalSize = $file->getSize();

        $image = $this->loadImage($file, $mime);
        $extension = $this->extensionFor($mime) ?? 'jpg';
        $path = 'visitor-photos/'.date('Y/m/d').'/'.Str::uuid().'.'.$extension;

        ob_start();
        imagejpeg($image, null, 80);
        $compressed = ob_get_clean();
        imagedestroy($image);

        Storage::disk('local')->put($path, $compressed);

        return [
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => 'image/jpeg',
            'original_size' => $originalSize,
            'compressed_size' => strlen($compressed),
        ];
    }

    private function loadImage(UploadedFile $file, string $mime)
    {
        $contents = file_get_contents($file->getRealPath());
        $image = match ($mime) {
            'image/png' => @imagecreatefromstring($contents),
            'image/webp' => @imagecreatefromstring($contents),
            default => @imagecreatefromjpeg($file->getRealPath()),
        };

        if (!$image) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        // Downscale very large images to a max dimension to keep files small.
        $maxDim = 1600;
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width > $maxDim || $height > $maxDim) {
            $ratio = min($maxDim / $width, $maxDim / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        return $image;
    }

    private function extensionFor(string $mime): ?string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/jpeg', 'image/jpg' => 'jpeg',
            default => null,
        };
    }
}
