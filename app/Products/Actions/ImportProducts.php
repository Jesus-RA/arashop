<?php

namespace App\Products\Actions;

use App\Inventory\Actions\AdjustStockAction;
use App\Inventory\Enums\InventoryTransactionType;
use App\Products\Enums\ProductStatus;
use App\Products\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImportProducts
{
    private const REQUIRED_COLUMNS = ['name', 'variant name'];

    /**
     * Friendly CSV header aliases, mapped to canonical internal keys.
     *
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'name' => 'name',
        'product' => 'name',
        'product name' => 'name',
        'description' => 'description',
        'status' => 'status',
        'variant name' => 'variant name',
        'variant' => 'variant name',
        'attributes' => 'attributes',
        'attribute' => 'attributes',
        'cost usd' => 'cost usd',
        'cost' => 'cost usd',
        'price mxn' => 'price mxn',
        'price' => 'price mxn',
        'mxn price' => 'price mxn',
        'stock quantity' => 'stock quantity',
        'stock' => 'stock quantity',
        'quantity' => 'stock quantity',
        'qty' => 'stock quantity',
    ];

    /**
     * Parses an uploaded CSV and bulk creates products with their variants.
     * One CSV row equals one variant; rows sharing a product name are grouped
     * into a single product.
     *
     * @return array{imported: int, variants: int, skipped: int, errors: list<string>}
     */
    public function handle(UploadedFile $file): array
    {
        [$products, $skipped, $errors] = $this->parse($file);

        if (empty($products)) {
            $messages = count($errors) > 0
                ? array_slice($errors, 0, 10)
                : ['No importable rows were found in the file.'];

            throw ValidationException::withMessages(['file' => $messages]);
        }

        [$imported, $variants] = $this->createProducts($products);

        return [
            'imported' => $imported,
            'variants' => $variants,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Parses an uploaded CSV and returns the products that would be imported
     * without persisting anything, so the client can preview the import.
     *
     * @return array{products: list<array{name: string, description: string|null, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>, skipped: int, errors: list<string>}
     */
    public function preview(UploadedFile $file): array
    {
        [$products, $skipped, $errors] = $this->parse($file);

        return [
            'products' => $this->previewProducts($products),
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{
     *     0: array<string, array{name: string, description: string, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>,
     *     1: int,
     *     2: list<string>
     * }
     */
    private function parse(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => ['The file is empty or could not be read as CSV.'],
            ]);
        }

        $columns = array_map(fn (string $column): string => $this->normalizeHeader($column), array_shift($rows));

        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_values($columns)));

        if (count($missing) > 0) {
            throw ValidationException::withMessages([
                'file' => ['Missing required column(s): '.implode(', ', $missing).'.'],
            ]);
        }

        return $this->parseRows($rows, array_flip($columns));
    }

    /**
     * @return list<list<?string>>
     */
    private function readRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        try {
            $rows = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function normalizeHeader(string $cell): string
    {
        $value = ltrim(trim($cell), "\xEF\xBB\xBF");
        $value = mb_strtolower($value);
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return self::HEADER_ALIASES[$value] ?? $value;
    }

    /**
     * @param  list<list<?string>>  $rows
     * @param  array<string, int>  $columnIndexes
     * @return array{
     *     0: array<string, array{name: string, description: string, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>,
     *     1: int,
     *     2: list<string>
     * }
     */
    private function parseRows(array $rows, array $columnIndexes): array
    {
        $products = [];
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            $line = $rowNumber + 2;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $data = $this->extractRow($row, $columnIndexes);

            $productName = $data['name'] ?? '';
            $variantName = $data['variant name'] ?? '';

            if ($productName === '') {
                $skipped++;
                $errors[] = "Row {$line}: the product name is required.";

                continue;
            }

            if ($productName !== '' && mb_strlen($productName) > 255) {
                $skipped++;
                $errors[] = "Row {$line}: the product name must be 255 characters or fewer.";

                continue;
            }

            if ($variantName === '') {
                $skipped++;
                $errors[] = "Row {$line}: the variant name is required.";

                continue;
            }

            if ($variantName !== '' && mb_strlen($variantName) > 255) {
                $skipped++;
                $errors[] = "Row {$line}: the variant name must be 255 characters or fewer.";

                continue;
            }

            $key = mb_strtolower($productName);

            $status = $data['status'] !== '' ? $data['status'] : ProductStatus::Draft->value;

            if (! in_array($status, ProductStatus::values(), true)) {
                $skipped++;
                $errors[] = "Row {$line}: the status must be one of draft, active or archived.";

                continue;
            }

            $costUsd = $this->parseDecimal($data['cost usd'] ?? '');

            if ($costUsd === null) {
                $skipped++;
                $errors[] = "Row {$line}: the cost usd must be a non-negative number.";

                continue;
            }

            $priceMxn = $this->parseDecimal($data['price mxn'] ?? '');

            if ($priceMxn === null) {
                $skipped++;
                $errors[] = "Row {$line}: the price mxn must be a non-negative number.";

                continue;
            }

            $stockQuantity = $this->parseStockQuantity($data['stock quantity'] ?? '');

            if ($stockQuantity === null) {
                $skipped++;
                $errors[] = "Row {$line}: the stock quantity must be a whole number of 0 or more.";

                continue;
            }

            try {
                $attributes = $this->parseAttributes($data['attributes'] ?? '');
            } catch (\InvalidArgumentException) {
                $skipped++;
                $errors[] = "Row {$line}: the attributes must use the key:value format separated by semicolons (\"Color:Red; Size:M\").";

                continue;
            }

            if (! isset($products[$key])) {
                $products[$key] = [
                    'name' => $productName,
                    'description' => $data['description'] ?? '',
                    'status' => $status,
                    'variants' => [],
                ];
            }

            $products[$key]['variants'][] = [
                'name' => $variantName,
                'attributes' => $attributes,
                'cost_usd' => $costUsd,
                'price_mxn' => $priceMxn,
                'stock_quantity' => $stockQuantity,
            ];
        }

        return [$products, $skipped, $errors];
    }

    /**
     * @param  list<?string>  $row
     * @param  array<string, int>  $columnIndexes
     * @return array<string, string>
     */
    private function extractRow(array $row, array $columnIndexes): array
    {
        $data = [];

        foreach ($columnIndexes as $column => $index) {
            $data[$column] = isset($row[$index]) && $row[$index] !== null ? trim((string) $row[$index]) : '';
        }

        return $data;
    }

    /**
     * @param  list<?string>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDecimal(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $decimal = (float) $value;

        return $decimal < 0 ? null : $decimal;
    }

    private function parseStockQuantity(string $value): ?int
    {
        if ($value === '') {
            return 0;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < 0) {
            return null;
        }

        return $integer;
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $attributes = [];

        foreach (explode(';', $value) as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            [$key, $attributeValue] = array_pad(explode(':', $segment, 2), 2, '');

            $key = trim($key);
            $attributeValue = trim($attributeValue);

            if ($key === '') {
                throw new \InvalidArgumentException('Empty attribute key.');
            }

            $attributes[$key] = $attributeValue;
        }

        return $attributes;
    }

    /**
     * @param  array<string, array{name: string, description: string, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>  $products
     * @return list<array{name: string, description: string|null, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>
     */
    private function previewProducts(array $products): array
    {
        return array_values(array_map(fn (array $product): array => [
            'name' => $product['name'],
            'description' => $product['description'] !== '' ? $product['description'] : null,
            'status' => $product['status'],
            'variants' => array_values(array_map(fn (array $variant): array => [
                'name' => $variant['name'],
                'attributes' => $variant['attributes'],
                'cost_usd' => $variant['cost_usd'],
                'price_mxn' => $variant['price_mxn'],
                'stock_quantity' => $variant['stock_quantity'],
            ], $product['variants'])),
        ], $products));
    }

    /**
     * @param  array<string, array{name: string, description: string, status: string, variants: list<array{name: string, attributes: array<string, string>, cost_usd: float|null, price_mxn: float|null, stock_quantity: int}>}>  $products
     * @return array{0: int, 1: int}
     */
    private function createProducts(array $products): array
    {
        $adjustStockAction = app(AdjustStockAction::class);

        $imported = 0;
        $variants = 0;

        DB::transaction(function () use ($products, $adjustStockAction, &$imported, &$variants): void {
            foreach ($products as $productData) {
                $product = Product::create([
                    'name' => $productData['name'],
                    'description' => $productData['description'] !== '' ? $productData['description'] : null,
                    'status' => $productData['status'],
                ]);

                foreach ($productData['variants'] as $variantData) {
                    $variant = $product->variants()->create([
                        'name' => $variantData['name'],
                        'attributes' => $variantData['attributes'],
                        'cost_usd' => $variantData['cost_usd'],
                        'price_mxn' => $variantData['price_mxn'],
                        'stock_quantity' => 0,
                    ]);

                    $variants++;

                    if ($variantData['stock_quantity'] > 0) {
                        $adjustStockAction->handle(
                            $variant,
                            $variantData['stock_quantity'],
                            InventoryTransactionType::Restock,
                            'initial',
                            'Bulk CSV import',
                            auth()->user(),
                        );
                    }
                }

                $imported++;
            }
        });

        return [$imported, $variants];
    }
}
