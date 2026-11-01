import { getField, updateField } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';
import PostRequest from '@/Helpers/API/PostRequest';

const debug = !true;

const state = {
  uuid: null,
  name: '',
  seed: 0,
  rng: null,
  mode: 'battleships',
  phase: 'lobby',
  turnColor: null,
  winner: null,

  board: [],
  players: [],
  currentPlayer: '',
  playerColor: '#93c5fd',

  myShips: [],
  placingShip: null,
  placingOrientation: 0,

  shots: [],
  sunkShips: [],
  sunkCells: [],
  explodingCells: [],

  readyPlayers: {},

  shipDefs: {},
  shipCounts: {
    carrier: 1,
    battleship: 1,
    cruiser: 1,
    submarine: 1,
    destroyer: 1,
  },

  settings: {
    perRow: 10,
    sort: 'random',
    pokedex: [],
    selectedPokedexLength: 0,
  },

  colors: {
    background: '#000000',
    hoverBorder: '#FF00EC',
    singleSelect: '#138e57',
    trackColor: '#133437',
  },
  cellSize: 60,
  cellSpacing: 1,
  showGridCoords: 'true',
  boardStatsPosition: 'top',
  shuffleBlanks: false,
  blankRows: false,

  hoverCell: { x: -1, y: -1 },
  readOnly: false,
};

const getters = {
  getField,

  expandedShipDefs: (state) => {
    const expanded = {};
    for (const [key, def] of Object.entries(state.shipDefs)) {
      const count = state.shipCounts[key] ?? 1;
      if (count === 1) {
        expanded[key] = def;
      } else {
        for (let i = 1; i <= count; i++) {
          expanded[`${key}_${i}`] = { ...def, label: `${def.label} ${i}`, baseType: key };
        }
      }
    }
    return expanded;
  },

  isOwner: (state) => {
    if (!state.currentPlayer || !state.players) return false;
    let owner = Object.values(state.players)?.find(p => p.owner === true);
    return state.currentPlayer === owner?.username;
  },

  isMyTurn: (state) => {
    if (state.phase !== 'battle') return false;
    const me = Object.values(state.players)?.find(p => p.username === state.currentPlayer);
    return me?.color === state.turnColor;
  },

  teamColors: (state) => {
    return [...new Set(Object.values(state.players).map(p => p.color))].sort();
  },

  isCellMyShip: (state) => (x, y) => {
    return state.myShips.some(ship =>
      ship.cells.some(c => c.x === x && c.y === y)
    );
  },

  getMyShipAt: (state) => (x, y) => {
    return state.myShips.find(ship =>
      ship.cells.some(c => c.x === x && c.y === y)
    ) || null;
  },

  isCellShot: (state) => (x, y) => {
    const me = Object.values(state.players)?.find(p => p.username === state.currentPlayer);
    const myColor = me?.color;
    return state.shots.find(s => s.x === x && s.y === y && s.shooterColor === myColor) || null;
  },

  isCellShotByAnyone: (state) => (x, y) => {
    return state.shots.find(s => s.x === x && s.y === y) || null;
  },

  getShipCells: () => (size, orientation, x, y) => {
    const cells = [];
    for (let i = 0; i < size; i++) {
      cells.push(orientation === 0 ? { x: x + i, y } : { x, y: y + i });
    }
    return cells;
  },

  isValidShipPlacement: (state, getters) => (shipType, orientation, x, y) => {
    const def = getters.expandedShipDefs[shipType];
    if (!def) return false;

    const cells = getters.getShipCells(def.size, orientation, x, y);
    const perRow = parseInt(state.settings.perRow) || 10;
    const rowCount = state.board.length;

    for (const c of cells) {
      if (c.x < 0 || c.y < 0 || c.x >= perRow || c.y >= rowCount) return false;
      if (state.board[c.y]?.[c.x] === null) return false;
      if (getters.isCellMyShip(c.x, c.y)) return false;
    }
    return true;
  },

  getHighlightedCells: (state, getters) => {
    const hx = state.hoverCell.x;
    const hy = state.hoverCell.y;
    if (hx < 0 || hy < 0) return [];

    if (state.phase === 'setup' && state.placingShip) {
      const def = getters.expandedShipDefs[state.placingShip];
      if (!def) return [];
      if (!getters.isValidShipPlacement(state.placingShip, state.placingOrientation, hx, hy)) return [];
      return getters.getShipCells(def.size, state.placingOrientation, hx, hy);
    }

    if (state.phase === 'battle' && getters.isMyTurn) {
      const perRow = parseInt(state.settings.perRow) || 10;
      if (hx >= perRow || hy >= state.board.length) return [];
      if (getters.isCellShot(hx, hy)) return [];
      return [{ x: hx, y: hy }];
    }

    return [];
  },

  allShipsPlaced: (state, getters) => {
    return state.myShips.length >= Object.keys(getters.expandedShipDefs).length;
  },

  sortPokedex: (state) => (pokedex) => {
    if (!pokedex || pokedex.length === 0) return [];
    let sorted = [...pokedex];
    switch (state.settings.sort) {
      case 'byId':
        sorted.sort((a, b) => a.id - b.id);
        break;
      case 'byName':
        sorted.sort((a, b) => a.name.localeCompare(b.name));
        break;
      case 'random':
      default:
        let rng = new RNG('dex' + state.seed);
        for (let i = sorted.length - 1; i > 0; i--) {
          const j = Math.floor(rng() * (i + 1));
          [sorted[i], sorted[j]] = [sorted[j], sorted[i]];
        }
        break;
    }
    return sorted;
  },

  renderCells: (state, getters) => {
    if (!state.board || state.board.length === 0) return [];
    const highlighted = getters.getHighlightedCells;
    const highlightSet = new Set(highlighted.map(c => c.x + ',' + c.y));

    return state.board.map((row, y) =>
      row.map((cell, x) => {
        const key = x + ',' + y;
        const isHighlighted = highlightSet.has(key);
        const cellShots = state.shots.filter(s => s.x === x && s.y === y);
        const isMyShip = getters.isCellMyShip(x, y);

        let bgColor = state.colors.background;
        let borderColor = 'transparent';
        let extraClass = '';

        const hexToRgba = (hex, a) => {
          const r = parseInt(hex.slice(1, 3), 16);
          const g = parseInt(hex.slice(3, 5), 16);
          const b = parseInt(hex.slice(5, 7), 16);
          return `rgba(${r}, ${g}, ${b}, ${a})`;
        };

        if (isMyShip) {
          const me = Object.values(state.players)?.find(p => p.username === state.currentPlayer);
          bgColor = me?.color || state.colors.singleSelect;
        }

        if (cellShots.length >= 2) {
          const colors = cellShots.map(s => hexToRgba(s.shooterColor, s.hit ? 0.5 : 0.2));
          bgColor = `linear-gradient(135deg, ${colors[0]} 50%, ${colors[1]} 50%)`;
          if (cellShots.some(s => s.hit)) borderColor = '#ef4444';
        } else if (cellShots.length === 1 && cellShots[0].shooterColor) {
          const shot = cellShots[0];
          if (shot.hit) {
            bgColor = hexToRgba(shot.shooterColor, 0.5);
            borderColor = '#ef4444';
          } else {
            bgColor = hexToRgba(shot.shooterColor, 0.2);
          }
        }

        if (isHighlighted) {
          borderColor = state.colors.hoverBorder;
        }

        return {
          key,
          class: {
            'opacity-30': cell === null,
          },
          style: {
            background: bgColor,
            '--borderColor': borderColor,
          },
        };
      })
    );
  },
};

const actions = {
  createRoom({ state }, data) {
    return new Promise((resolve, reject) => {
      PostRequest('/battleships/', data, (response) => {
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  setUuid({ commit }, uuid) {
    commit('updateField', { path: 'uuid', value: uuid });
  },

  setReadOnly({ commit }, value) {
    commit('updateField', { path: 'readOnly', value: !!value });
  },

  setPlayers({ commit }, players) {
    commit('SET_PLAYERS', players);
  },

  setCurrentPlayer({ commit }, player) {
    if (typeof player !== 'string' || player.trim() === '') return;
    commit('updateField', { path: 'currentPlayer', value: player });
  },

  setShipDefs({ commit }, defs) {
    commit('updateField', { path: 'shipDefs', value: defs });
  },

  setState({ state, commit }, data) {
    if (data.perRow) commit('updateField', { path: 'settings.perRow', value: parseInt(data.perRow) });
    if (data.pokedex) commit('updateField', { path: 'settings.pokedex', value: data.pokedex });
    if (data.sort) commit('updateField', { path: 'settings.sort', value: data.sort });

    commit('updateField', { path: 'name', value: data.name || state.name });
    commit('updateField', { path: 'seed', value: data.seed ?? state.seed });
    commit('updateField', { path: 'phase', value: data.phase || 'lobby' });
    commit('updateField', { path: 'turnColor', value: data.turnColor ?? null });
    commit('updateField', { path: 'winner', value: data.winner ?? null });

    if (data.showGridCoords !== undefined) commit('updateField', { path: 'showGridCoords', value: data.showGridCoords });
    if (data.cellSize !== undefined) commit('updateField', { path: 'cellSize', value: parseInt(data.cellSize) });
    if (data.cellSpacing !== undefined) commit('updateField', { path: 'cellSpacing', value: parseInt(data.cellSpacing) });
    if (data.boardStatsPosition !== undefined) commit('updateField', { path: 'boardStatsPosition', value: data.boardStatsPosition });
    if (data.shuffleBlanks !== undefined) commit('updateField', { path: 'shuffleBlanks', value: data.shuffleBlanks });
    if (data.blankRows !== undefined) commit('updateField', { path: 'blankRows', value: data.blankRows });
    if (data.shipCounts) commit('updateField', { path: 'shipCounts', value: data.shipCounts });

    if (data.shots) commit('updateField', { path: 'shots', value: data.shots });
    if (data.myShips) commit('updateField', { path: 'myShips', value: data.myShips });
    if (data.sunkCells) commit('updateField', { path: 'sunkCells', value: data.sunkCells });
    if (data.readyPlayers) commit('updateField', { path: 'readyPlayers', value: data.readyPlayers });
  },

  setBoard({ state, commit, getters }, { board, perRow }) {
    if (!Array.isArray(board)) return;

    let sorted = getters.sortPokedex(board);
    let numPerRow = parseInt(perRow) || 10;
    let paddingBlanks = sorted.length % numPerRow === 0 ? 0 : numPerRow - (sorted.length % numPerRow);
    let extraBlanks = (state.blankRows === true || state.blankRows === 'true') ? numPerRow : 0;
    let blanksCount = paddingBlanks + extraBlanks;

    let flat;
    if ((state.shuffleBlanks === true || state.shuffleBlanks === 'true') && blanksCount > 0) {
      let totalCells = sorted.length + blanksCount;
      let rng = new RNG('blanks' + state.seed);

      let nullPositions = new Set();
      let attempts = 0;
      let maxAttempts = totalCells * 10;
      while (nullPositions.size < blanksCount && attempts < maxAttempts) {
        attempts++;
        let pos = Math.floor(rng() * totalCells);
        if (nullPositions.has(pos)) continue;
        let col = pos % numPerRow;
        let left = col > 0 ? pos - 1 : -1;
        let right = col < numPerRow - 1 ? pos + 1 : -1;
        let above = pos - numPerRow;
        let below = pos + numPerRow;
        let adjacent = false;
        if (left >= 0 && nullPositions.has(left)) adjacent = true;
        if (right >= 0 && right < totalCells && nullPositions.has(right)) adjacent = true;
        if (above >= 0 && nullPositions.has(above)) adjacent = true;
        if (below < totalCells && nullPositions.has(below)) adjacent = true;
        if (!adjacent) nullPositions.add(pos);
      }

      flat = new Array(totalCells);
      let pokemonIdx = 0;
      for (let i = 0; i < totalCells; i++) {
        if (nullPositions.has(i)) {
          flat[i] = null;
        } else {
          flat[i] = sorted[pokemonIdx++] || null;
        }
      }
    } else {
      flat = [...sorted];
      for (let i = 0; i < blanksCount; i++) flat.push(null);
    }

    let updateBoard = flat.reduce((acc, curr, index) => {
      if (index % numPerRow === 0) acc.push([]);
      acc[acc.length - 1].push(curr);
      return acc;
    }, []);

    commit('updateField', { path: 'board', value: updateBoard });
    let boardLength = 0;
    updateBoard.forEach(row => row.forEach(cell => { if (cell !== null) boardLength++; }));
    commit('updateField', { path: 'settings.perRow', value: perRow });
    commit('updateField', { path: 'settings.selectedPokedexLength', value: boardLength });
  },

  setRNG({ state, commit }) {
    let rng = new RNG(state.seed);
    commit('updateField', { path: 'rng', value: rng });
  },

  setHoverCell({ commit }, { x, y }) {
    commit('updateField', { path: 'hoverCell', value: { x, y } });
  },

  placeShip({ state, commit, getters }, { shipType, orientation, x, y }) {
    if (state.phase !== 'setup') return Promise.reject('Not in setup phase');
    if (!getters.isValidShipPlacement(shipType, orientation, x, y)) return Promise.reject('Invalid placement');

    const def = getters.expandedShipDefs[shipType];
    const cells = getters.getShipCells(def.size, orientation, x, y);

    const ships = [...state.myShips, { shipType, cells, orientation }];
    commit('updateField', { path: 'myShips', value: ships });

    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/place-ship`, {
        shipType,
        cells,
        orientation,
      }, (response) => {
        resolve(response);
      }, (error) => {
        const reverted = state.myShips.filter(s => s.shipType !== shipType);
        commit('updateField', { path: 'myShips', value: reverted });
        reject(error);
      });
    });
  },

  removeShip({ state, commit }, shipType) {
    if (state.phase !== 'setup') return Promise.reject('Not in setup phase');

    const removed = state.myShips.filter(s => s.shipType !== shipType);
    const backup = [...state.myShips];
    commit('updateField', { path: 'myShips', value: removed });

    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/remove-ship`, {
        shipType,
      }, (response) => {
        resolve(response);
      }, (error) => {
        commit('updateField', { path: 'myShips', value: backup });
        reject(error);
      });
    });
  },

  toggleReady({ state, commit }) {
    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/ready`, {}, (response) => {
        const ready = response.data.ready;
        const updated = { ...state.readyPlayers, [state.currentPlayer]: ready };
        commit('updateField', { path: 'readyPlayers', value: updated });

        if (response.data.phase === 'battle') {
          commit('updateField', { path: 'phase', value: 'battle' });
          commit('updateField', { path: 'turnColor', value: response.data.turnColor || null });
        }
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  fireShot({ state, commit, getters }, { x, y }) {
    if (!getters.isMyTurn) return Promise.reject('Not your turn');
    if (getters.isCellShot(x, y)) return Promise.reject('Already shot');

    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/fire`, { x, y }, (response) => {
        const { hit, sunkShip, turnColor, winner, phase } = response.data;

        const me = Object.values(state.players)?.find(p => p.username === state.currentPlayer);
        const shots = [...state.shots, { x, y, hit, shooterColor: me?.color }];
        commit('updateField', { path: 'shots', value: shots });
        commit('updateField', { path: 'turnColor', value: turnColor });

        if (hit) {
          const key = `${x},${y}`;
          commit('updateField', { path: 'explodingCells', value: [...state.explodingCells, key] });
          setTimeout(() => {
            commit('updateField', {
              path: 'explodingCells',
              value: state.explodingCells.filter(c => c !== key),
            });
          }, 800);
        }

        if (sunkShip) {
          const sunk = [...state.sunkShips, sunkShip];
          commit('updateField', { path: 'sunkShips', value: sunk });
          const { sunkShipCells } = response.data;
          if (sunkShipCells) {
            const cells = [...state.sunkCells, ...sunkShipCells.map(c => `${c.x},${c.y}`)];
            commit('updateField', { path: 'sunkCells', value: cells });
          }
        }

        if (phase === 'finished') {
          commit('updateField', { path: 'phase', value: 'finished' });
          commit('updateField', { path: 'winner', value: winner });
        }
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  addShot({ state, commit }, shot) {
    const shots = [...state.shots, shot];
    commit('updateField', { path: 'shots', value: shots });

    if (shot.hit) {
      const key = `${shot.x},${shot.y}`;
      commit('updateField', { path: 'explodingCells', value: [...state.explodingCells, key] });
      setTimeout(() => {
        commit('updateField', {
          path: 'explodingCells',
          value: state.explodingCells.filter(c => c !== key),
        });
      }, 800);
    }

    if (shot.sunkShip) {
      const sunk = [...state.sunkShips, shot.sunkShip];
      commit('updateField', { path: 'sunkShips', value: sunk });
      if (shot.sunkShipCells) {
        const cells = [...state.sunkCells, ...shot.sunkShipCells.map(c => `${c.x},${c.y}`)];
        commit('updateField', { path: 'sunkCells', value: cells });
      }
    }
  },

  setPhase({ commit }, { phase, turnColor }) {
    commit('updateField', { path: 'phase', value: phase });
    if (turnColor !== undefined) commit('updateField', { path: 'turnColor', value: turnColor });
  },

  setPlayerReady({ state, commit }, { username, ready }) {
    const updated = { ...state.readyPlayers, [username]: ready };
    commit('updateField', { path: 'readyPlayers', value: updated });
  },

  startGame({ state, commit }) {
    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/start-game`, {}, (response) => {
        commit('updateField', { path: 'phase', value: 'setup' });
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  saveRoom({ state, commit }) {
    let saveData = new FormData();
    saveData.append('name', state.name);
    saveData.append('seed', state.seed);
    saveData.append('pokedex', JSON.stringify(state.settings.pokedex));
    saveData.append('perRow', state.settings.perRow);
    saveData.append('sort', state.settings.sort);
    saveData.append('showGridCoords', state.showGridCoords);
    saveData.append('cellSize', state.cellSize);
    saveData.append('cellSpacing', state.cellSpacing);
    saveData.append('boardStatsPosition', state.boardStatsPosition);
    saveData.append('shuffleBlanks', state.shuffleBlanks ? 'true' : 'false');
    saveData.append('blankRows', (state.blankRows === true || state.blankRows === 'true') ? 'true' : 'false');
    saveData.append('shipCounts', JSON.stringify(state.shipCounts));

    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/save`, saveData, (response) => {
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  updatePlayer({ state, commit }, data) {
    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/update-player`, {
        username: data.username,
        color: data.color,
      }, (response) => {
        commit('SET_PLAYERS', response.data.players);
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },

  newPlayer({ state, commit }, data) {
    return new Promise((resolve, reject) => {
      PostRequest(`/battleships/${state.uuid}/join`, data.player, (response) => {
        commit('SET_PLAYERS', response.data.players);
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },
};

const mutations = {
  getField,
  updateField,
  SET_PLAYERS: (state, players) => {
    if (players !== undefined && players !== null) {
      state.players = players;
    }
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
