<?php

namespace App\Products\Actions;

use Illuminate\Http\UploadedFile;

final class OptimizeProductImage
{
    private const PNG_COMPRESSION_LEVEL = 9;

    private const JPEG_QUALITY = 85;

    public function handle(UploadedFile $file): UploadedFile
    {
        $mime = $file->getMimeType();

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            default => false,
        };

        if ($source === false) {
            return $file;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $output = imagecreatetruecolor($width, $height);

        if ($output === false) {
            return $file;
        }

        imagealphablending($output, false);
        imagesavealpha($output, true);

        imagecopyresampled($output, $source, 0, 0, 0, 0, $width, $height, $width, $height);

        $tempPath = tempnam(sys_get_temp_dir(), 'arashop-image-');

        $saved = $mime === 'image/png'
            ? imagepng($output, $tempPath, self::PNG_COMPRESSION_LEVEL)
            : imagejpeg($output, $tempPath, self::JPEG_QUALITY);

        if ($saved === false) {
            @unlink($tempPath);

            return $file;
        }

        return new UploadedFile($tempPath, $file->getClientOriginalName(), $mime, null, true);
    }
}
