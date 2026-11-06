import { getField, updateField } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';

const shapes = {
  triangle(perRow) {
    const mask = [];
    const midX = Math.floor(perRow / 2);
    const rows = Math.floor(perRow / 2) + 1;
    for (let y = 0; y < rows; y++) {
      for (let x = midX - y; x <= midX + y; x++) {
        if (x >= 0 && x < perRow) mask.push({ x, y });
      }
    }
    return { mask, rows };
  },

  diamond(perRow) {
    const mask = [];
    const midX = Math.floor(perRow / 2);
    const halfHeight = Math.floor(perRow / 3);
    const totalRows = halfHeight * 2 + 1;
    for (let y = 0; y < totalRows; y++) {
      const width = y <= halfHeight ? y : totalRows - 1 - y;
      for (let x = midX - width; x <= midX + width; x++) {
        if (x >= 0 && x < perRow) mask.push({ x, y });
      }
    }
    return { mask, rows: totalRows };
  },

  crazy(perRow, seed) {
    const rng = new RNG('crazy' + seed);
    const totalCells = 151;
    const rows = Math.ceil(totalCells / perRow) + 4;
    const gridSize = perRow * rows;
    const cellsToRemove = gridSize - totalCells;

    const grid = Array.from({ length: rows }, () => Array(perRow).fill(true));

    function stampCells(cells) {
      let count = 0;
      for (const [cx, cy] of cells) {
        if (cy >= 0 && cy < rows && cx >= 0 && cx < perRow && grid[cy][cx]) {
          grid[cy][cx] = false;
          count++;
        }
      }
      return count;
    }

    function makeCircle(ox, oy, r) {
      const cells = [];
      for (let dy = -r; dy <= r; dy++) {
        for (let dx = -r; dx <= r; dx++) {
          if (dx * dx + dy * dy <= r * r) cells.push([ox + dx, oy + dy]);
        }
      }
      return cells;
    }

    function makeSquare(ox, oy, size) {
      const cells = [];
      for (let dy = 0; dy < size; dy++) {
        for (let dx = 0; dx < size; dx++) {
          cells.push([ox + dx, oy + dy]);
        }
      }
      return cells;
    }

    function makeTriangle(ox, oy, size, dir) {
      const cells = [];
      for (let row = 0; row < size; row++) {
        const width = dir === 0 ? row + 1 : size - row;
        const startX = ox - Math.floor(width / 2);
        for (let dx = 0; dx < width; dx++) {
          cells.push([startX + dx, oy + row]);
        }
      }
      return cells;
    }

    const shapeTypes = ['circle', 'square', 'triangle'];
    let removed = 0;
    let attempts = 0;

    while (removed < cellsToRemove && attempts < 500) {
      attempts++;
      const type = shapeTypes[Math.floor(rng() * shapeTypes.length)];
      let cells;

      if (type === 'circle') {
        const r = Math.floor(rng() * 2) + 1;
        const ox = Math.floor(rng() * (perRow - 2 * r)) + r;
        const oy = Math.floor(rng() * (rows - 2 * r)) + r;
        cells = makeCircle(ox, oy, r);
      } else if (type === 'square') {
        const size = Math.floor(rng() * 2) + 2;
        const ox = Math.floor(rng() * (perRow - size));
        const oy = Math.floor(rng() * (rows - size));
        cells = makeSquare(ox, oy, size);
      } else {
        const size = Math.floor(rng() * 2) + 3;
        const dir = Math.floor(rng() * 2);
        const ox = Math.floor(rng() * (perRow - size)) + Math.floor(size / 2);
        const oy = Math.floor(rng() * (rows - size));
        cells = makeTriangle(ox, oy, size, dir);
      }

      const inBounds = cells.filter(([cx, cy]) =>
        cy >= 0 && cy < rows && cx >= 0 && cx < perRow
      );

      if (inBounds.length === 0) continue;

      const margin = 1;
      let tooClose = false;
      for (const [cx, cy] of inBounds) {
        for (let dy = -margin; dy <= margin && !tooClose; dy++) {
          for (let dx = -margin; dx <= margin && !tooClose; dx++) {
            if (dx === 0 && dy === 0) continue;
            const nx = cx + dx, ny = cy + dy;
            if (ny >= 0 && ny < rows && nx >= 0 && nx < perRow && !grid[ny][nx]) {
              const isNeighborInShape = inBounds.some(([sx, sy]) => sx === nx && sy === ny);
              if (!isNeighborInShape) tooClose = true;
            }
          }
        }
      }

      if (tooClose) continue;

      const validCells = inBounds.filter(([cx, cy]) => grid[cy][cx]);
      if (validCells.length > 0 && removed + validCells.length <= cellsToRemove) {
        removed += stampCells(cells);
      }
    }

    // Remove individual cells to hit exactly 151 if shaped cutouts weren't enough
    if (removed < cellsToRemove) {
      const remaining = [];
      for (let y = 0; y < rows; y++) {
        for (let x = 0; x < perRow; x++) {
          if (grid[y][x]) remaining.push({ x, y });
        }
      }
      // Shuffle remaining cells and remove from the end to reach target
      for (let i = remaining.length - 1; i > 0; i--) {
        const j = Math.floor(rng() * (i + 1));
        [remaining[i], remaining[j]] = [remaining[j], remaining[i]];
      }
      while (removed < cellsToRemove && remaining.length > totalCells) {
        const cell = remaining.pop();
        grid[cell.y][cell.x] = false;
        removed++;
      }
    }

    // Trim empty trailing rows
    let actualRows = rows;
    while (actualRows > 0 && grid[actualRows - 1].every(c => !c)) {
      actualRows--;
    }

    const mask = [];
    for (let y = 0; y < actualRows; y++) {
      for (let x = 0; x < perRow; x++) {
        if (grid[y][x]) mask.push({ x, y });
      }
    }
    return { mask, rows: actualRows };
  },

  donut(perRow) {
    const mask = [];
    const size = Math.min(perRow, 11);
    const holeStart = Math.floor(size * 0.3);
    const holeEnd = size - holeStart - 1;
    const offsetX = Math.floor((perRow - size) / 2);
    for (let y = 0; y < size; y++) {
      for (let dx = 0; dx < size; dx++) {
        const isHole = dx >= holeStart && dx <= holeEnd && y >= holeStart && y <= holeEnd;
        if (!isHole) {
          mask.push({ x: offsetX + dx, y });
        }
      }
    }
    return { mask, rows: size };
  },
};

const state = {
  board: [],
  perRow: 15,
  seed: 42,
  shape: null,
  cellSize: 60,
  cellSpacing: 1,
  showGridCoords: 1,
  colors: {
    background: '#000000',
    hoverBorder: '#FF00EC',
    singleSelect: '#138e57',
  },
  selectedCells: [],
  hoverCell: { x: -1, y: -1 },
};

const getters = {
  getField,

  isCellSelected: (state) => (x, y) => {
    return state.selectedCells.some(c => c.x === x && c.y === y);
  },

  shapeMask: (state) => {
    if (!state.shape || !shapes[state.shape]) return { mask: [], rows: 0 };
    return shapes[state.shape](state.perRow, state.seed);
  },

  availableShapes: () => Object.keys(shapes),
};

const mutations = {
  updateField,
};

const actions = {
  generateBoard({ state, commit, getters }, pokedexData) {
    if (!pokedexData || !state.shape) return;

    const pokemon = Object.values(pokedexData).slice(0, 151);
    const rng = new RNG('dex' + state.seed);
    const shuffled = [...pokemon];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(rng() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    const { mask, rows } = getters.shapeMask;
    const maskSet = new Set(mask.map(c => `${c.x},${c.y}`));

    const board = [];
    let pokemonIdx = 0;
    for (let y = 0; y < rows; y++) {
      const row = [];
      for (let x = 0; x < state.perRow; x++) {
        if (maskSet.has(`${x},${y}`)) {
          row.push(shuffled[pokemonIdx % shuffled.length] || null);
          pokemonIdx++;
        } else {
          row.push(null);
        }
      }
      board.push(row);
    }

    commit('updateField', { path: 'board', value: board });
  },

  selectCell({ state, commit }, { x, y }) {
    const idx = state.selectedCells.findIndex(c => c.x === x && c.y === y);
    if (idx >= 0) {
      const cells = [...state.selectedCells];
      cells.splice(idx, 1);
      commit('updateField', { path: 'selectedCells', value: cells });
    } else {
      commit('updateField', { path: 'selectedCells', value: [...state.selectedCells, { x, y }] });
    }
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
