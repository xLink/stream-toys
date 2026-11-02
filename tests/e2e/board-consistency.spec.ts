import { test, expect } from '@playwright/test';
import { loginAsPlayer1, loginAsPlayer2, getBoardCells, TETRIS_ROOM_ID, BATTLESHIPS_ROOM_ID } from './helpers';

test.describe('Board consistency - TetrisMP', () => {
  test('player and spectator see the same board', async ({ context }) => {
    const playerPage = await context.newPage();
    await loginAsPlayer1(playerPage);
    await playerPage.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);

    const spectatorPage = await context.newPage();
    await spectatorPage.goto(`/tetris-mp/${TETRIS_ROOM_ID}/spectate`);

    const playerCells = await getBoardCells(playerPage);
    const spectatorCells = await getBoardCells(spectatorPage);

    expect(playerCells.length).toBeGreaterThan(0);
    expect(playerCells.length).toBe(spectatorCells.length);

    for (let i = 0; i < playerCells.length; i++) {
      expect(playerCells[i].x, `cell ${i} x mismatch`).toBe(spectatorCells[i].x);
      expect(playerCells[i].y, `cell ${i} y mismatch`).toBe(spectatorCells[i].y);
      expect(playerCells[i].pokemon, `cell ${i} pokemon mismatch at (${playerCells[i].x},${playerCells[i].y})`).toBe(spectatorCells[i].pokemon);
    }

    await playerPage.close();
    await spectatorPage.close();
  });

  test('both players see the same board', async ({ context }) => {
    const player1Page = await context.newPage();
    await loginAsPlayer1(player1Page);
    await player1Page.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);

    const player2Page = await context.newPage();
    await loginAsPlayer2(player2Page);
    await player2Page.goto(`/tetris-mp/${TETRIS_ROOM_ID}`);

    const player1Cells = await getBoardCells(player1Page);
    const player2Cells = await getBoardCells(player2Page);

    expect(player1Cells.length).toBeGreaterThan(0);
    expect(player1Cells.length).toBe(player2Cells.length);

    for (let i = 0; i < player1Cells.length; i++) {
      expect(player1Cells[i].pokemon, `cell pokemon mismatch at (${player1Cells[i].x},${player1Cells[i].y})`).toBe(player2Cells[i].pokemon);
    }

    await player1Page.close();
    await player2Page.close();
  });
});

test.describe('Board consistency - Battleships', () => {
  test('player and spectator see the same board', async ({ context }) => {
    const playerPage = await context.newPage();
    await loginAsPlayer1(playerPage);
    await playerPage.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);

    const spectatorPage = await context.newPage();
    await spectatorPage.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);

    const playerCells = await getBoardCells(playerPage);
    const spectatorCells = await getBoardCells(spectatorPage);

    expect(playerCells.length).toBeGreaterThan(0);
    expect(playerCells.length).toBe(spectatorCells.length);

    for (let i = 0; i < playerCells.length; i++) {
      expect(playerCells[i].x, `cell ${i} x mismatch`).toBe(spectatorCells[i].x);
      expect(playerCells[i].y, `cell ${i} y mismatch`).toBe(spectatorCells[i].y);
      expect(playerCells[i].pokemon, `cell ${i} pokemon mismatch at (${playerCells[i].x},${playerCells[i].y})`).toBe(spectatorCells[i].pokemon);
    }

    await playerPage.close();
    await spectatorPage.close();
  });

  test('owner and second player see the same board', async ({ context }) => {
    // Player2 joins first
    const player2Page = await context.newPage();
    await loginAsPlayer2(player2Page);
    await player2Page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    await player2Page.getByRole('button', { name: /Join Room/i }).click({ timeout: 10000 });
    await expect(player2Page.getByText('testplayer2')).toBeVisible({ timeout: 10000 });

    const player1Page = await context.newPage();
    await loginAsPlayer1(player1Page);
    await player1Page.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);

    const player1Cells = await getBoardCells(player1Page);
    const player2Cells = await getBoardCells(player2Page);

    expect(player1Cells.length).toBeGreaterThan(0);
    expect(player1Cells.length).toBe(player2Cells.length);

    for (let i = 0; i < player1Cells.length; i++) {
      expect(player1Cells[i].pokemon, `cell pokemon mismatch at (${player1Cells[i].x},${player1Cells[i].y})`).toBe(player2Cells[i].pokemon);
    }

    await player1Page.close();
    await player2Page.close();
  });

  test('all three views match: player1, player2, spectator', async ({ context }) => {
    // Player2 joins
    const p2 = await context.newPage();
    await loginAsPlayer2(p2);
    await p2.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);
    // Player2 might already be joined from previous test, handle both cases
    const joinBtn = p2.getByRole('button', { name: /Join Room/i });
    if (await joinBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
      await joinBtn.click();
      await expect(p2.getByText('testplayer2')).toBeVisible({ timeout: 10000 });
    }

    const p1 = await context.newPage();
    await loginAsPlayer1(p1);
    await p1.goto(`/battleships/${BATTLESHIPS_ROOM_ID}`);

    const spec = await context.newPage();
    await spec.goto(`/battleships/${BATTLESHIPS_ROOM_ID}/spectate`);

    const [cells1, cells2, cellsSpec] = await Promise.all([
      getBoardCells(p1),
      getBoardCells(p2),
      getBoardCells(spec),
    ]);

    expect(cells1.length).toBeGreaterThan(0);
    expect(cells1.length).toBe(cells2.length);
    expect(cells1.length).toBe(cellsSpec.length);

    for (let i = 0; i < cells1.length; i++) {
      const coord = `(${cells1[i].x},${cells1[i].y})`;
      expect(cells1[i].pokemon, `p1 vs p2 at ${coord}`).toBe(cells2[i].pokemon);
      expect(cells1[i].pokemon, `p1 vs spectator at ${coord}`).toBe(cellsSpec[i].pokemon);
    }

    await p1.close();
    await p2.close();
    await spec.close();
  });
});
