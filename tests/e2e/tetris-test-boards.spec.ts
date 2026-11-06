import { test, expect } from '@playwright/test';

test.describe('Tetris Test Boards', () => {
  test('index page lists all shapes', async ({ page }) => {
    await page.goto('/tetris/test');
    await expect(page.getByRole('heading', { name: 'Tetris Test Boards' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'triangle' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'diamond' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'donut' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'crazy' })).toBeVisible();
  });

  test('triangle board renders shaped grid', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    // Board cells should exist
    const cells = page.locator('[data-testid^="cell-"]');
    const count = await cells.count();
    expect(count).toBeGreaterThan(0);
    // Triangle: row 0 has 1 cell, grows wider — first row should have fewer cells than last
    const row0 = page.locator('[data-testid^="cell-"][data-y="0"]');
    const row5 = page.locator('[data-testid^="cell-"][data-y="5"]');
    expect(await row0.count()).toBeLessThan(await row5.count());
  });

  test('diamond board renders shaped grid', async ({ page }) => {
    await page.goto('/tetris/test/diamond');
    await expect(page.getByRole('heading', { name: 'diamond' })).toBeVisible({ timeout: 10000 });
    const cells = page.locator('[data-testid^="cell-"]');
    const count = await cells.count();
    expect(count).toBeGreaterThan(0);
    // Diamond: middle row is widest, top and bottom rows are narrow
    const row0 = page.locator('[data-testid^="cell-"][data-y="0"]');
    const midY = Math.floor(count / 10);
    const rowMid = page.locator(`[data-testid^="cell-"][data-y="${midY}"]`);
    expect(await row0.count()).toBeLessThan(await rowMid.count());
  });

  test('donut board renders shaped grid with hollow center', async ({ page }) => {
    await page.goto('/tetris/test/donut');
    await expect(page.getByRole('heading', { name: 'donut' })).toBeVisible({ timeout: 10000 });
    const cells = page.locator('[data-testid^="cell-"]');
    const count = await cells.count();
    expect(count).toBeGreaterThan(0);
    // Donut: outer rows (top/bottom) should be full width, middle rows should have fewer
    // cells because of the hole
    const row0 = page.locator('[data-testid^="cell-"][data-y="0"]');
    const midRow = page.locator('[data-testid^="cell-"][data-y="5"]');
    const row0Count = await row0.count();
    const midCount = await midRow.count();
    // Middle row has hole, so fewer cells than edge row
    expect(midCount).toBeLessThan(row0Count);
  });

  test('crazy board renders full grid with cutouts', async ({ page }) => {
    await page.goto('/tetris/test/crazy');
    await expect(page.getByRole('heading', { name: 'crazy' })).toBeVisible({ timeout: 10000 });
    const cells = page.locator('[data-testid^="cell-"]');
    const count = await cells.count();
    // Full grid with spaced cutouts, exactly 151 cells for all gen1 pokemon
    expect(count).toBe(151);
  });

  test('each shape has a different cell count', async ({ page }) => {
    const counts: number[] = [];
    for (const shape of ['triangle', 'diamond', 'donut', 'crazy']) {
      await page.goto(`/tetris/test/${shape}`);
      await expect(page.getByRole('heading', { name: shape })).toBeVisible({ timeout: 10000 });
      const cells = page.locator('[data-testid^="cell-"]');
      counts.push(await cells.count());
    }
    expect(new Set(counts).size).toBe(4);
  });

  test('cells outside shape are invisible', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    // Row 0 of triangle: only 1 cell should have data-testid (the tip)
    const row0Cells = page.locator('[data-testid^="cell-"][data-y="0"]');
    expect(await row0Cells.count()).toBe(1);
  });

  test('clicking a cell toggles its caught state', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    // Click the tip cell
    const tipCell = page.locator('[data-testid^="cell-"][data-y="0"]').first();
    await tipCell.click();
    // Caught count should update
    await expect(page.locator('text=1 Caught')).toBeVisible();
    // Click again to deselect
    await tipCell.click();
    await expect(page.locator('text=0 Caught')).toBeVisible();
  });

  test('shape cells have pokemon data', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    const cell = page.locator('[data-testid^="cell-"]').first();
    await expect(cell).toHaveAttribute('data-pokemon', /.+/);
  });

  test('shape navigation links work', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    await page.getByRole('link', { name: 'diamond' }).first().click();
    await expect(page).toHaveURL(/\/tetris\/test\/diamond/);
    await expect(page.getByRole('heading', { name: 'diamond' })).toBeVisible({ timeout: 10000 });
  });

  test('back link returns to shape index', async ({ page }) => {
    await page.goto('/tetris/test/triangle');
    await expect(page.getByRole('heading', { name: 'triangle' })).toBeVisible({ timeout: 10000 });
    await page.getByRole('link', { name: '← All Shapes' }).click();
    await expect(page).toHaveURL(/\/tetris\/test$/);
    await expect(page.getByRole('link', { name: 'triangle' })).toBeVisible();
  });

  test('invalid shape returns 404', async ({ page }) => {
    const response = await page.goto('/tetris/test/invalid-shape');
    expect(response?.status()).toBe(404);
  });
});
