import { Page, expect } from '@playwright/test';

export const PLAYER1_ID = '11111111-1111-1111-1111-111111111111';
export const PLAYER2_ID = '22222222-2222-2222-2222-222222222222';
export const SPECTATOR_ID = '33333333-3333-3333-3333-333333333333';
export const TETRIS_ROOM_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
export const BATTLESHIPS_ROOM_ID = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

export async function loginAs(page: Page, userId: string) {
  await page.goto(`/test-login/${userId}`);
  await page.waitForURL('**/');
}

export async function loginAsPlayer1(page: Page) {
  await loginAs(page, PLAYER1_ID);
}

export async function loginAsPlayer2(page: Page) {
  await loginAs(page, PLAYER2_ID);
}

export async function getBoardCells(page: Page): Promise<{ x: string; y: string; pokemon: string }[]> {
  await page.waitForSelector('[data-testid^="cell-"]', { timeout: 10000 });
  return page.$$eval('[data-testid^="cell-"]', (cells) =>
    cells.map((cell) => ({
      x: cell.getAttribute('data-x') || '',
      y: cell.getAttribute('data-y') || '',
      pokemon: cell.getAttribute('data-pokemon') || '',
    }))
  );
}
