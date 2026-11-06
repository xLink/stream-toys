import { test, expect } from '@playwright/test';
import { loginAsPlayer1, BATTLESHIPS_MULTI_ROOM_ID } from './helpers';

// Helper to get a ship button in the toolbar
function shipBtn(page, name: string) {
  return page.locator('.bg-slate-800 div.cursor-pointer:has-text("' + name + '")');
}

test.describe('Battleships - Multiple Ship Copies', () => {
  test('toolbar shows numbered copies for destroyer (x3) and submarine (x2)', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Destroyer x3 should show Destroyer 1, 2, 3
    await expect(shipBtn(page, 'Destroyer 1')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 2')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 3')).toBeVisible();

    // Submarine x2 should show Submarine 1, 2
    await expect(shipBtn(page, 'Submarine 1')).toBeVisible();
    await expect(shipBtn(page, 'Submarine 2')).toBeVisible();

    // Single-count ships keep their original names
    await expect(shipBtn(page, 'Carrier')).toBeVisible();
    await expect(shipBtn(page, 'Battleship')).toBeVisible();
    await expect(shipBtn(page, 'Cruiser')).toBeVisible();
  });

  test('no unnumbered destroyer or submarine in toolbar', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Plain "Destroyer" (without number) should not exist in the toolbar
    const destroyerExact = page.locator('.bg-slate-800 div.cursor-pointer').filter({ hasText: /^(?:.*\s)?Destroyer$/ }).filter({ hasNotText: /\d/ });
    await expect(destroyerExact).toHaveCount(0);
  });
});

test.describe.serial('Battleships - Place Multiple Copies', () => {
  test('can place all three destroyer copies', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Place Destroyer 1 (size 2) at (0,0) — horizontal → (0,0), (1,0)
    await shipBtn(page, 'Destroyer 1').click();
    await page.locator('[data-testid="cell-0-0"]').click();
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Place Destroyer 2 at (0,2) — horizontal → (0,2), (1,2)
    await shipBtn(page, 'Destroyer 2').click();
    await page.locator('[data-testid="cell-0-2"]').click();
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Place Destroyer 3 at (0,4) — horizontal → (0,4), (1,4)
    await shipBtn(page, 'Destroyer 3').click();
    await page.locator('[data-testid="cell-0-4"]').click();
    await expect(shipBtn(page, 'Destroyer 3').locator('text=✓')).toBeVisible({ timeout: 5000 });
  });

  test('each copy is independent — removing one keeps the others', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // All three should be placed
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Destroyer 3').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Remove Destroyer 2
    await shipBtn(page, 'Destroyer 2').click();
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).not.toBeVisible({ timeout: 5000 });

    // Destroyer 1 and 3 should still be placed
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 3').locator('text=✓')).toBeVisible();
  });

  test('can re-place a removed copy without affecting others', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Destroyer 2 should be removed, 1 and 3 placed
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).not.toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Re-place Destroyer 2 at (0,6)
    await shipBtn(page, 'Destroyer 2').click();
    await page.locator('[data-testid="cell-0-6"]').click();
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // All three placed again
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 3').locator('text=✓')).toBeVisible();
  });

  test('can place both submarine copies alongside destroyers', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Place Submarine 1 (size 3) at (4,0) — horizontal → (4,0), (5,0), (6,0)
    await shipBtn(page, 'Submarine 1').click();
    await page.locator('[data-testid="cell-4-0"]').click();
    await expect(shipBtn(page, 'Submarine 1').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Place Submarine 2 at (4,2) — horizontal → (4,2), (5,2), (6,2)
    await shipBtn(page, 'Submarine 2').click();
    await page.locator('[data-testid="cell-4-2"]').click();
    await expect(shipBtn(page, 'Submarine 2').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // All destroyers still placed
    await expect(shipBtn(page, 'Destroyer 1').locator('text=✓')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 2').locator('text=✓')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer 3').locator('text=✓')).toBeVisible();
  });

  test('right-click removes only the copy on that cell', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_MULTI_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // All submarines and destroyers placed
    await expect(shipBtn(page, 'Submarine 1').locator('text=✓')).toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Submarine 2').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Right-click on cell (4,0) where Submarine 1 starts
    await page.locator('[data-testid="cell-4-0"]').click({ button: 'right' });

    // Only Submarine 1 removed
    await expect(shipBtn(page, 'Submarine 1').locator('text=✓')).not.toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Submarine 2').locator('text=✓')).toBeVisible();
  });
});
