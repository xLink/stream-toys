import { test, expect } from '@playwright/test';
import { loginAsPlayer1, loginAsPlayer2, BATTLESHIPS_SETUP_ROOM_ID } from './helpers';

// Helper to get a ship button in the toolbar (not the sidebar)
function shipBtn(page, name: string) {
  return page.locator('.bg-slate-800 div.cursor-pointer:has-text("' + name + '")');
}

test.describe('Battleships - Ship Placement (Setup Phase)', () => {
  test('setup phase shows ship selector toolbar', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
    await expect(shipBtn(page, 'Carrier')).toBeVisible();
    await expect(shipBtn(page, 'Battleship')).toBeVisible();
    await expect(shipBtn(page, 'Cruiser')).toBeVisible();
    await expect(shipBtn(page, 'Submarine')).toBeVisible();
    await expect(shipBtn(page, 'Destroyer')).toBeVisible();
  });

  test('shows rotate hint', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('text=Press')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('kbd:has-text("R")')).toBeVisible();
  });

  test('clicking a ship button selects it', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    const btn = shipBtn(page, 'Destroyer');
    await btn.click();
    await expect(btn).toHaveClass(/bg-blue-600/);
  });

  test('clicking the same ship button deselects it', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    const btn = shipBtn(page, 'Destroyer');
    await btn.click();
    await expect(btn).toHaveClass(/bg-blue-600/);
    await btn.click();
    await expect(btn).toHaveClass(/bg-slate-700/);
  });
});

test.describe.serial('Battleships - Place and Remove Ship', () => {
  test('can place a destroyer on the board', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Select destroyer (size 2, horizontal by default)
    const btn = shipBtn(page, 'Destroyer');
    await btn.click();
    await expect(btn).toHaveClass(/bg-blue-600/);

    // Click cell (0,0) to place — occupies (0,0) and (1,0) horizontally
    await page.locator('[data-testid="cell-0-0"]').click();

    // Ship placed — checkmark appears on the button
    await expect(btn.locator('text=✓')).toBeVisible({ timeout: 5000 });
  });

  test('placed ship persists on page reload', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Destroyer should still show checkmark
    await expect(shipBtn(page, 'Destroyer').locator('text=✓')).toBeVisible({ timeout: 5000 });
  });

  test('can remove a placed ship by clicking its toolbar button', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    const btn = shipBtn(page, 'Destroyer');
    await expect(btn.locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Clicking a placed ship's button removes it and selects for re-placement
    await btn.click();
    await expect(btn.locator('text=✓')).not.toBeVisible({ timeout: 5000 });
  });

  test('can re-place a ship after removing it', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Select destroyer
    const btn = shipBtn(page, 'Destroyer');
    await btn.click();
    await expect(btn).toHaveClass(/bg-blue-600/);

    // Place at (2,2)
    await page.locator('[data-testid="cell-2-2"]').click();
    await expect(btn.locator('text=✓')).toBeVisible({ timeout: 5000 });
  });

  test('can remove a ship by right-clicking its cells', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    const btn = shipBtn(page, 'Destroyer');
    await expect(btn.locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Right-click on cell (2,2) where the destroyer was placed
    await page.locator('[data-testid="cell-2-2"]').click({ button: 'right' });

    // Destroyer should no longer be placed
    await expect(btn.locator('text=✓')).not.toBeVisible({ timeout: 5000 });
  });
});

test.describe.serial('Battleships - Place Multiple Ships', () => {
  test('can place multiple different ships', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Place destroyer (size 2) at (0,0)
    await shipBtn(page, 'Destroyer').click();
    await page.locator('[data-testid="cell-0-0"]').click();
    await expect(shipBtn(page, 'Destroyer').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Deselect destroyer before selecting cruiser
    await shipBtn(page, 'Destroyer').click();

    // Place cruiser (size 3) at (0,2)
    await shipBtn(page, 'Cruiser').click();
    await page.locator('[data-testid="cell-0-2"]').click();
    await expect(shipBtn(page, 'Cruiser').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Both should show checkmarks
    await expect(shipBtn(page, 'Destroyer').locator('text=✓')).toBeVisible();
    await expect(shipBtn(page, 'Cruiser').locator('text=✓')).toBeVisible();
  });

  test('removing one ship does not affect other placed ships', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_SETUP_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });

    // Both should be placed
    await expect(shipBtn(page, 'Destroyer').locator('text=✓')).toBeVisible({ timeout: 5000 });
    await expect(shipBtn(page, 'Cruiser').locator('text=✓')).toBeVisible({ timeout: 5000 });

    // Remove destroyer
    await shipBtn(page, 'Destroyer').click();
    await expect(shipBtn(page, 'Destroyer').locator('text=✓')).not.toBeVisible({ timeout: 5000 });

    // Cruiser should still be placed
    await expect(shipBtn(page, 'Cruiser').locator('text=✓')).toBeVisible();
  });
});
