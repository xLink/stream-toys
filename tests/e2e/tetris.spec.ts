import { test, expect } from '@playwright/test';

test.describe('Tetris', () => {
  test('index page loads', async ({ page }) => {
    await page.goto('/tetris');
    await expect(page).toHaveURL(/\/tetris/);
  });

  test('board renders with cells', async ({ page }) => {
    await page.goto('/tetris');
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });

  test('cells have pokemon data attributes', async ({ page }) => {
    await page.goto('/tetris');
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    const cell = page.locator('[data-testid="cell-0-0"]');
    await expect(cell).toHaveAttribute('data-pokemon', /.+/);
  });

  test('board has multiple rows', async ({ page }) => {
    await page.goto('/tetris');
    await page.waitForSelector('[data-row]', { timeout: 10000 });
    const rows = page.locator('[data-row]');
    expect(await rows.count()).toBeGreaterThan(1);
  });
});
