import { test, expect } from '@playwright/test';
import { loginAsPlayer1, loginAsPlayer2, loginAs, BATTLESHIPS_ROOM_ID, SPECTATOR_ID } from './helpers';

test.describe('Battleships - Index', () => {
  test('index page loads when logged in', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/battleships');
    await expect(page.getByRole('heading', { name: 'Pokémon Battleships', exact: true })).toBeVisible();
  });

  test('index page shows login button when not authenticated', async ({ page }) => {
    await page.goto('/battleships');
    await expect(page.locator('text=Login with Discord')).toBeVisible();
  });

  test('index page shows create room form when logged in', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/battleships');
    await expect(page.locator('text=Create a new room')).toBeVisible();
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('text=Create Room')).toBeVisible();
  });

  test('index page has ship count controls', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/battleships');
    await expect(page.locator('text=Ship Counts')).toBeVisible();
  });

  test('index page has pokedex selection', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/battleships');
    await expect(page.locator('text=Select Pokédex')).toBeVisible();
  });

  test('create room navigates to new room', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto('/battleships');

    await page.locator('input[name="name"]').fill('E2E Battleships Room');
    await page.locator('button:has-text("Create Room")').click();

    await page.waitForURL(/\/battleships\/[a-f0-9-]+/, { timeout: 10000 });
    await expect(page.getByRole('heading', { name: 'Pokémon Battleships', exact: true })).toBeVisible({ timeout: 10000 });
  });
});

test.describe('Battleships - Room', () => {
  test('room loads for owner', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.getByRole('heading', { name: 'Pokémon Battleships', exact: true })).toBeVisible({ timeout: 10000 });
    await expect(page.getByText('testplayer1')).toBeVisible();
  });

  test('room renders board with cells', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });

  test('board cells have pokemon data', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    const cell = page.locator('[data-testid="cell-0-0"]');
    await expect(cell).toHaveAttribute('data-pokemon', /.+/);
  });

  test('board has multiple cells across the grid', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    await expect(page.locator('[data-testid="cell-4-0"]')).toBeVisible();
    await expect(page.locator('[data-testid="cell-0-4"]')).toBeVisible();
  });

  test('lobby shows player list with count', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.getByRole('heading', { name: /Players \(\d+\)/ })).toBeVisible({ timeout: 10000 });
  });

  test('phase banner shows all phases', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    const bannerPhases = page.locator('.z-10');
    const texts = await bannerPhases.allTextContents();
    expect(texts).toContain('Lobby');
    expect(texts).toContain('Place Ships');
    expect(texts).toContain('Battle');
    expect(texts).toContain('Finished');
  });

  test('owner sees start game button', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.getByRole('button', { name: /Start Game/i })).toBeVisible({ timeout: 10000 });
  });

  test('start game button is disabled with one team color', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.getByRole('button', { name: /Start Game/i })).toBeDisabled({ timeout: 10000 });
    await expect(page.getByText('Need at least 2 different team colors')).toBeVisible();
  });

  test('owner does not see leave button', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    await expect(page.getByRole('button', { name: /Leave Room/i })).not.toBeVisible();
  });
});

test.describe.serial('Battleships - Join/Leave flow', () => {
  test('new player sees join button', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    // Leave first if already joined from a prior test
    const leaveBtn = page.getByRole('button', { name: /Leave Room/i });
    if (await leaveBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
      await leaveBtn.click();
    }
    await expect(page.getByRole('button', { name: /Join Room/i })).toBeVisible({ timeout: 15000 });
  });

  test('player can join room', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.getByRole('button', { name: /Join Room/i }).click({ timeout: 10000 });
    await expect(page.getByText('testplayer2')).toBeVisible({ timeout: 10000 });
  });

  test('joined player sees leave button', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    // Player2 is already joined from previous test
    await expect(page.getByRole('button', { name: /Leave Room/i })).toBeVisible({ timeout: 10000 });
  });

  test('owner sees kick button for other players', async ({ page }) => {
    await loginAsPlayer1(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await expect(page.getByText('testplayer2')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('button[title="Kick player"]')).toBeVisible();
  });

  test('player can leave room', async ({ page }) => {
    await loginAsPlayer2(page);
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await page.getByRole('button', { name: /Leave Room/i }).click({ timeout: 10000 });
    // After leaving, page reloads and shows join button
    await expect(page.getByRole('button', { name: /Join Room/i })).toBeVisible({ timeout: 10000 });
  });
});

test.describe('Battleships - Spectate', () => {
  test('spectate route loads', async ({ page }) => {
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);
    await expect(page.getByText('Spectating')).toBeVisible({ timeout: 10000 });
  });

  test('spectate shows board', async ({ page }) => {
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);
    await expect(page.locator('[data-testid="cell-0-0"]')).toBeVisible({ timeout: 10000 });
  });

  test('spectate shows phase banner', async ({ page }) => {
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    const bannerPhases = page.locator('.z-10');
    const texts = await bannerPhases.allTextContents();
    expect(texts).toContain('Lobby');
  });

  test('spectate does not show join or start buttons', async ({ page }) => {
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);
    await page.waitForSelector('[data-testid="cell-0-0"]', { timeout: 10000 });
    await expect(page.getByRole('button', { name: /Join Room/i })).not.toBeVisible();
    await expect(page.getByRole('button', { name: /Start Game/i })).not.toBeVisible();
  });

  test('spectate shows player list', async ({ page }) => {
    await page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);
    await expect(page.getByText('testplayer1')).toBeVisible({ timeout: 10000 });
  });
});
