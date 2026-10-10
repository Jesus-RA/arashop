<?php

namespace Tests\Unit;

use App\Products\Actions\OptimizeProductImage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class OptimizeProductImageTest extends TestCase
{
    public function test_losslessly_recompresses_pngs(): void
    {
        $bloated = $this->buildPng(1200, 1200, 0);
        $file = UploadedFile::fake()->createWithContent('photo.png', $bloated);

        $optimized = (new OptimizeProductImage)->handle($file);

        $this->assertNotSame($file, $optimized);
        $this->assertLessThan(strlen($bloated) / 2, filesize($optimized->getRealPath()));
        $this->assertSame('image/png', $optimized->getMimeType());

        $info = getimagesize($optimized->getRealPath());

        $this->assertNotFalse($info);
        $this->assertSame([1200, 1200], [$info[0], $info[1]]);

        @unlink($optimized->getRealPath());
    }

    public function test_reencodes_jpegs_as_valid_jpeg(): void
    {
        $image = imagecreatetruecolor(24, 24);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 40));

        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        $file = UploadedFile::fake()->createWithContent('photo.jpg', $jpeg);

        $optimized = (new OptimizeProductImage)->handle($file);

        $this->assertNotSame($file, $optimized);
        $this->assertSame('image/jpeg', $optimized->getMimeType());
        $this->assertNotFalse(getimagesize($optimized->getRealPath()));

        @unlink($optimized->getRealPath());
    }

    public function test_returns_original_file_when_it_cannot_be_decoded(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.png', 'this is not an image');

        $this->assertSame($file, (new OptimizeProductImage)->handle($file));
    }

    private function buildPng(int $width, int $height, int $compressionLevel, int $color = 0xFFFFFF): string
    {
        $row = "\x00".str_repeat(pack('N', $color), $width);
        $raw = str_repeat($row, $height);

        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.hash('crc32b', $type.$data, true);

        $png = "\x89PNG\r\n\x1a\n";
        $png .= $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0));
        $png .= $chunk('IDAT', gzcompress($raw, $compressionLevel));
        $png .= $chunk('IEND', '');

        return $png;
    }
}
