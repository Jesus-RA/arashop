import { test, expect } from '@playwright/test';

async function login(page) {
    await page.goto('/login');
    await expect(page).toHaveURL(/\/login$/);
    await page.getByLabel('Email').first().fill('ale@arashop.test');
    await page.getByLabel('Password').first().fill('password');
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/$/);
}

test.describe('authentication', () => {
    test('redirects unauthenticated users to login', async ({ page }) => {
        await page.goto('/products');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('logs in and out', async ({ page }) => {
        await login(page);

        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

        await page.getByRole('button', { name: 'Ale' }).click();
        await page.getByText('Log out').click();
        await expect(page).toHaveURL(/\/login$/);
    });
});

test.describe('theme', () => {
    test('toggles between light and dark mode', async ({ page }) => {
        await login(page);

        const app = page.locator('.v-application').first();
        const toggle = page.getByRole('button', { name: 'Switch to dark mode' });

        await expect(toggle).toBeVisible();
        await expect(app).toHaveClass(/v-theme--light/);

        await toggle.click();
        await expect(page.getByRole('button', { name: 'Switch to light mode' })).toBeVisible();
        await expect(app).toHaveClass(/v-theme--dark/);

        await page.reload();
        await expect(page.getByRole('button', { name: 'Switch to light mode' })).toBeVisible();
    });
});

test.describe('products', () => {
    test('creates, views and edits a product', async ({ page }) => {
        const name = `E2E Tee ${Date.now()}`;
        const price = '350';

        await login(page);
        await page.goto('/products/new');
        await expect(page.getByRole('heading', { name: 'New product' })).toBeVisible();

        await page.getByLabel('Name').first().fill(name);
        await page.getByLabel('Variant name').fill('White / M');
        await page.getByLabel('Cost USD').fill('12.50');
        await page.getByLabel('Price MXN').fill(price);
        await page.getByLabel('Qty').fill('10');
        await page.getByRole('button', { name: 'Create product' }).click();

        await expect(page).toHaveURL(/\/products\/\d+$/);
        await expect(page.getByRole('heading', { name })).toBeVisible();
        await expect(page.getByText('MX$350.00')).toBeVisible();

        await page.getByRole('link', { name: 'Edit' }).click();
        await expect(page).toHaveURL(/\/edit$/);

        await expect(page.getByLabel('Price MXN')).not.toHaveValue('');
        await page.getByLabel('Price MXN').fill('389');
        await page.getByRole('button', { name: 'Save changes' }).click();

        await expect(page).toHaveURL(/\/products\/\d+$/);
        await expect(page.getByText('MX$389.00')).toBeVisible();
    });

    test('deletes a product', async ({ page }) => {
        const name = `E2E Delete Me ${Date.now()}`;

        await login(page);
        await page.goto('/products/new');
        await page.getByLabel('Name').first().fill(name);
        await page.getByLabel('Variant name').fill('Black / L');
        await page.getByLabel('Cost USD').fill('8.00');
        await page.getByLabel('Price MXN').fill('250');
        await page.getByLabel('Qty').fill('5');
        await page.getByRole('button', { name: 'Create product' }).click();
        await expect(page).toHaveURL(/\/products\/\d+$/);

        await page.getByRole('button', { name: 'Delete', exact: true }).click();
        await page.getByRole('button', { name: 'Delete', exact: true }).last().click();

        await expect(page).toHaveURL(/\/products$/);
        await expect(page.getByText(name)).not.toBeVisible();
    });
});

test.describe('stock', () => {
    test('adjusts stock and shows history', async ({ page }) => {
        const name = `E2E Stock ${Date.now()}`;

        await login(page);
        await page.goto('/products/new');
        await page.getByLabel('Name').first().fill(name);
        await page.getByLabel('Variant name').fill('Blue / XL');
        await page.getByLabel('Cost USD').fill('9.75');
        await page.getByLabel('Price MXN').fill('280');
        await page.getByLabel('Qty').fill('20');
        await page.getByRole('button', { name: 'Create product' }).click();
        await expect(page).toHaveURL(/\/products\/\d+$/);

        await page.goto('/stock');
        await expect(page.getByRole('heading', { name: 'Stock' })).toBeVisible();

        await page.getByRole('textbox', { name: 'Search stock' }).fill(name);

        const row = page.locator('tr', { hasText: name });
        await expect(row).toBeVisible();

        await row.getByRole('button', { name: 'Adjust' }).click();
        await page.getByLabel('Quantity change', { exact: true }).fill('-3');
        await page.getByLabel('Reason').first().fill('sale');
        await page.getByLabel('Reason').first().press('Enter');
        await page.getByLabel('Notes').fill('Sold three units');
        await page.getByRole('button', { name: 'Apply' }).click();

        await expect(row.getByText('17')).toBeVisible();

        await row.getByRole('button', { name: 'History' }).click();
        await expect(page.getByText('Stock history')).toBeVisible();
        await expect(page.getByText('sale')).toBeVisible();
    });
});

test.describe('settings & pricing', () => {
    test('loads and saves settings', async ({ page }) => {
        await login(page);
        await page.goto('/settings');
        await expect(page.getByRole('heading', { name: 'Settings' })).toBeVisible();

        const fx = page.getByLabel('USD to MXN rate', { exact: true });
        await expect(fx).not.toHaveValue('0');
        const original = await fx.inputValue();

        await fx.fill('18.4');
        await page.getByRole('button', { name: 'Save settings' }).click();
        await expect(page.getByText('Settings saved')).toBeVisible();
        await expect(fx).toHaveValue('18.4');

        await fx.fill(original);
        await page.getByRole('button', { name: 'Save settings' }).click();
        await expect(page.getByText('Settings saved')).toBeVisible();
        await expect(fx).toHaveValue(original);
    });

    test('calculates a suggested price', async ({ page }) => {
        await login(page);
        await page.goto('/pricing');
        await expect(page.getByRole('heading', { name: 'Pricing calculator' })).toBeVisible();

        await page.getByLabel('Cost (USD)').fill('10');
        await page.getByLabel('Shipping (USD)').fill('2');
        await page.getByLabel('FX rate (USD → MXN)', { exact: true }).fill('18');
        await page.getByLabel('Margin (%)', { exact: true }).fill('35');
        await page.getByLabel('Platform fee (%)', { exact: true }).fill('10');
        await page.getByLabel('Packaging fee (MXN)', { exact: true }).fill('15');
        await page.getByLabel('Discount (%)', { exact: true }).fill('0');

        await expect(page.getByText('MX$420.00')).toBeVisible();
        await expect(page.getByText('MX$147.00')).toBeVisible();
    });
});