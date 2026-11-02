import { test, expect } from '@playwright/test';
import { loginAsPlayer1, loginAsPlayer2, TETRIS_ROOM_ID } from './helpers';

test.describe('Tetris MP', () => {
  test('index page loads', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/tetris-mp');
    await expect(page.locator('text=Multiplayer Tetris')).toBeVisible();
  });

  test('shows login button when not authenticated', async ({ page }) => {
    await page.goto('/tetris-mp');
    await expect(page.locator('text=Login with Discord')).toBeVisible();
  });

  test('create room form is visible when logged in', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/tetris-mp');
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('text=Room Info')).toBeVisible();
  });

  test('create room form has required fields', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/tetris-mp');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="seed"]')).toBeVisible();
    await expect(page.locator('text=Create Room')).toBeVisible();
  });

  test('seeded room loads with board', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);
    await expect(page.locator('h1')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });

  test('room board cells have pokemon data', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    const cell = page.locator('[data-testid="cell-0-0"]');
    await expect(cell).toHaveAttribute('data-pokemon', /.+/);
  });

  test('room shows sidebar with players', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);
    await expect(page.getByText('testplayer1')).toBeVisible({ timeout: 10000 });
  });

  test('spectate route loads', async ({ page }) => {
    await page.goto(`/tetris-mp/${TETRIS_ROOM_ID}/spectate`);
    await expect(page.locator('text=Spectating')).toBeVisible({ timeout: 10000 });
  });

  test('spectate shows board cells', async ({ page }) => {
    await page.goto(`/tetris-mp/${TETRIS_ROOM_ID}/spectate`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });

  test('create room navigates to new room', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/tetris-mp');

    await page.locator('input[name="name"]').fill('E2E Test Room');
    await page.locator('label:has-text("Co-op")').locator('input[type="radio"]').check();
    await page.locator('text=Create Room').click();

    await page.waitForURL(/\/tetris-mp\/[a-f0-9-]+/, { timeout: 10000 });
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });
});
