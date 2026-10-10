<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_upload_requires_authentication(): void
    {
        $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->image('photo.png')])
            ->assertUnauthorized();
    }

    public function test_png_image_uploads_and_returns_public_path(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->image('photo.png', 10, 10)])
            ->assertCreated()
            ->assertJsonPath('image_path', fn ($value) => str_starts_with($value, '/storage/products/'));

        Storage::disk('public')->assertExists(ltrim($this->imagePath($response), '/storage/'));
    }

    public function test_jpeg_image_uploads(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->image('photo.jpg', 10, 10)])
            ->assertCreated()
            ->assertJsonPath('image_path', fn ($value) => str_ends_with($value, '.jpg'));
    }

    public function test_gif_images_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->image('photo.gif', 10, 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_webp_images_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->image('photo.webp', 10, 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_non_image_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/products/images', ['image' => UploadedFile::fake()->create('notes.txt', 100)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_max_size_error_is_reported_in_megabytes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/products/images', ['image' => $this->oversizedPng()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image'])
            ->assertJsonPath('errors.image.0', 'The image must be 4 MB or smaller.');
    }

    private function oversizedPng(): UploadedFile
    {
        $width = 1100;
        $height = 1100;
        $raw = '';

        for ($row = 0; $row < $height; $row++) {
            $raw .= "\x00".random_bytes($width * 4);
        }

        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.hash('crc32b', $type.$data, true);

        $png = "\x89PNG\r\n\x1a\n";
        $png .= $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0));
        $png .= $chunk('IDAT', gzcompress($raw, 9));
        $png .= $chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('big.png', $png);
    }

    private function imagePath($response): string
    {
        return $response->json('image_path');
    }
}
