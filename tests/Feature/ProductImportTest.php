<?php

namespace Tests\Feature;

use App\Models\User;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'name,description,status,variant name,attributes,cost usd,price mxn,stock quantity';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_import_requires_authentication(): void
    {
        $file = UploadedFile::fake()->createWithContent('products.csv', self::HEADER);

        $this->post('/api/products/import', ['file' => $file])->assertUnauthorized();
    }

    public function test_import_requires_a_csv_file(): void
    {
        $this->actingAs($this->user)
            ->post('/api/products/import')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_imports_products_and_variants_from_csv(): void
    {
        $csv = implode("\n", [
            self::HEADER,
            'Ergonomic Mouse,Wireless 2.4G,active,Ergonomic Mouse - Black,Color:Black;Size:Standard,12.5,299,25',
            'Ergonomic Mouse,Wireless 2.4G,active,Ergonomic Mouse - White,Color:White;Size:Standard,13,310,0',
            'Desk Mat,,draft,Desk Mat - Large,Size:Large,8,199,10',
        ]);

        $this->actingAs($this->user)
            ->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('variants', 3)
            ->assertJsonPath('skipped', 0)
            ->assertJsonCount(0, 'errors');

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 3);
        $this->assertDatabaseCount('stock_adjustments', 2);

        $product = Product::where('name', 'Ergonomic Mouse')->firstOrFail();
        $this->assertSame('active', $product->status->value);
        $this->assertNull($product->sku);
        $this->assertSame('Wireless 2.4G', $product->description);
        $this->assertSame(2, $product->variants()->count());

        $this->assertSame(25, ProductVariant::where('name', 'Ergonomic Mouse - Black')->firstOrFail()->stock_quantity);
        $this->assertSame(0, ProductVariant::where('name', 'Ergonomic Mouse - White')->firstOrFail()->stock_quantity);
        $this->assertSame('Black', ProductVariant::where('name', 'Ergonomic Mouse - Black')->firstOrFail()->attributes['Color']);
    }

    public function test_import_rejects_files_missing_required_columns(): void
    {
        $csv = "name,description,status\nErgonomic Mouse,Wireless,active";

        $this->actingAs($this->user)
            ->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('file')
            ->assertJsonPath('errors.file.0', 'Missing required column(s): variant name.');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_skips_invalid_rows_and_reports_errors(): void
    {
        $csv = implode("\n", [
            self::HEADER,
            'Logitech Mouse,,active,Logitech Mouse Black,,10,250,5',
            'Broken Status,,,,,,',
            'Negative Cost,,active,Negative Cost Variant,,5,-6,0',
        ]);

        $this->actingAs($this->user)
            ->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('skipped', 2)
            ->assertJsonCount(2, 'errors');

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_preview_returns_importable_products_without_persisting(): void
    {
        $csv = implode("\n", [
            self::HEADER,
            'Ergonomic Mouse,Wireless 2.4G,active,Ergonomic Mouse - Black,Color:Black,12.5,299,25',
            'Ergonomic Mouse,Wireless 2.4G,active,Ergonomic Mouse - White,Color:White,13,310,0',
            'Desk Mat,,draft,Desk Mat - Large,Size:Large,8,199,10',
        ]);

        $this->actingAs($this->user)
            ->post('/api/products/import/preview', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('skipped', 0)
            ->assertJsonCount(2, 'products')
            ->assertJsonPath('products.0.name', 'Ergonomic Mouse')
            ->assertJsonPath('products.0.status', 'active')
            ->assertJsonCount(2, 'products.0.variants')
            ->assertJsonPath('products.0.variants.0.stock_quantity', 25)
            ->assertJsonPath('products.1.name', 'Desk Mat');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_preview_reports_the_reasons_skipped_rows_were_excluded(): void
    {
        $csv = implode("\n", [
            self::HEADER,
            'Logitech Mouse,,active,Logitech Mouse Black,,10,250,5',
            'Broken Status,,,,,,',
            'Negative Cost,,active,Negative Cost Variant,,5,-6,0',
        ]);

        $this->actingAs($this->user)
            ->post('/api/products/import/preview', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('skipped', 2)
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Logitech Mouse')
            ->assertJsonCount(2, 'errors')
            ->assertJsonPath('errors.0', 'Row 3: the variant name is required.')
            ->assertJsonPath('errors.1', 'Row 4: the price mxn must be a non-negative number.');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_ignores_sku_columns_if_present(): void
    {
        $csv = implode("\n", [
            'name,description,status,sku,variant name,variant sku,attributes,cost usd,price mxn,stock quantity',
            'First Mouse,,active,ARA-001,First Mouse Variant,VS-1,,10,200,5',
        ]);

        $this->actingAs($this->user)
            ->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertDatabaseCount('products', 1);

        $product = Product::firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->assertNull($product->sku);
        $this->assertNull($variant->sku);
    }

    public function test_import_rejects_an_empty_file(): void
    {
        $this->actingAs($this->user)
            ->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('products.csv', ''),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('file');
    }
}
