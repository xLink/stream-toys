import { getField, updateField } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';
import PostRequest from '@/Helpers/API/PostRequest';

const debug = !true;

const state = {
  name: '',
  uuid: null,
  seed: null,
  lastSaved: new Date().toISOString(),
  mode: 'blackout',
  currentPlayer: null,
  viewingPlayer: null,
  playerColor: 'blue',
  step: 0,
  
  rng: null,
  players: [],
  settings: {
    perRow: 10,
    sort: 'random',
    tetriminosToGenerate: 3,
    pokedex: ['kanto'],
    selectedPokedexLength: 151,
    selectionType: 'tetris',
    perPlayer: !true,
  },
  cellSize: 60,
  cellSpacing: 1,
  showGridCoords: true,

  board: [],
  showHistory: true,
  history: [],
  selectedHistoryId: null,
  selectedCells: [],
  trackedCells: [], 
  hoverCell: { x: -1, y: -1 },

  useTetrisColors: 'player',
  pieceSelection: 0,
  rotation: 0,
  pieceGeneration: [
    { type: 'i', rotation: 0 },
    { type: 'j', rotation: 0 },
    { type: 'l', rotation: 0 },
    { type: 'o', rotation: 0 },
    { type: 's', rotation: 0 },
    { type: 't', rotation: 0 },
    { type: 'z', rotation: 0 },
    { type: '.', rotation: 0 },
  ],
  pieceDef: {},
  
  colors: {
    background: '#000000',
    hoverBorder: '#FF00EC',
    singleSelect: '#138e57',
    trackColor: '#133437',
  },
};

const getters = {
  getField,

  viewingUsername: (state) => state.viewingPlayer || state.currentPlayer,

  isOwner: (state) => {
    if (!state.currentPlayer || !state.players) {
      return false;
    }
    let owner = Object.values(state.players)?.find(player => player.owner === true);
    if (owner === null) {
      return false;
    }
    return state.currentPlayer === owner?.username;
  },

  selectedPiece: (state) => {
    if (state.pieceGeneration.length === 0) {
      return { type: '.', rotation: state.rotation };
    }
    return state.pieceGeneration[state.pieceSelection] || { type: '.', rotation: state.rotation };
  },

  getTetriminoCoords: (state, getters) => (type, rotation, x, y, ignoreChecks=false) => {
    if (state.settings.selectionType !== 'tetris') {
      type = '.';
      rotation = 0;
    }

    // console.log('Generating Tetrimino Coords:', type, rotation, x, y);
    let activePiece = state.pieceDef[type];
    if (!activePiece) {
      if (debug) console.error('No active piece defined for Tetrimino:', activePiece);
      return [];
    }
    let activePieceType = type;

    rotation = rotation || 0;
    x = parseInt(x) || 0;
    y = parseInt(y) || 0;

    if (x === null || y === null) {
      if (debug) console.error('Invalid coordinates provided for Tetrimino:', x, y);
      return [];
    }
    if (x < 0 || y < 0) {
      if (debug) console.error('Coordinates cannot be negative:', x, y);
      return [];
    }

    if (!state.pieceDef[activePieceType]) {
      if (debug) console.error('Piece definition not found for type:', activePieceType, Object.keys(state.pieceDef));
      return [];
    }

    let startPos = state.pieceDef[activePieceType].start[rotation] || [0, 0];
    x -= startPos[0];
    y -= startPos[1];

    let row = 0;
    let col = 0;
    let returnCells = [];

    let blocks = state.pieceDef[activePieceType].blocks[rotation];
    for (let i = 0; i < blocks.length; i++) {
      for (let j = 0; j < blocks[i].length; j++) {
        if (blocks[i][j]) {
          returnCells.push({ x: row + x, y: col + y });
        }
        if (++col === 4) {
          col = 0;
          ++row;
        }
      }
    }

    if (returnCells.length === 0) {
      if (debug) console.error('No valid hover cells generated for Tetrimino:', returnCells.map(cell => `${cell.x}, ${cell.y}`));
      return [];
    }

    if (ignoreChecks) {
      return returnCells;
    }

    // if any of the returnCells are out of bounds, return empty array
    // console.info('Checking hover cell bounds:', state.hoverCell.x < 0, state.hoverCell.y < 0);
    if (state.hoverCell.x < 0 || state.hoverCell.y < 0) {
      if (debug) console.error('Hover cell is out of bounds 1:', state.hoverCell);
      return [];
    }

    // console.info('Hover cell x:', state.hoverCell.x, 'perRow:', state.perRow, state.hoverCell.x > state.perRow)
    if (state.hoverCell.x > state.perRow) {
      if (debug) console.error('Hover cell is out of bounds 2:', state.hoverCell.x, state.settings.perRow);
      return [];
    }

    let rowLength = (state.selectedPokedexLength / state.perRow);
    // console.info('Hover cell y:', state.hoverCell.y, 'rowLength:', rowLength, state.hoverCell.y >= rowLength);
    if (state.hoverCell.y >= rowLength) {
      if (debug) console.error('Hover cell is out of bounds 3:', state.hoverCell.y, (state.settings.selectedPokedexLength / state.settings.perRow));
      return [];
    }

    // if any of the returnCells are already selected, return empty array
    if (state.selectedCells.length && returnCells.some(cell => getters.isCellSelected(cell.x, cell.y))) {
      if (debug) console.error(
        'Some return cells are already selected:', 
        returnCells.map(cell => `${cell.x}, ${cell.y}`), 
        state.selectedCells.map(cell => `${cell.x}, ${cell.y}`)
      );
      return [];
    }

    // make sure all returnCells are in the grid bounds
    if (returnCells.some(cell => cell.x < 0 || cell.y < 0 || cell.x >= state.settings.perRow || cell.y >= (state.settings.selectedPokedexLength / state.settings.perRow))) {
      if (debug) console.error('Some return cells are out of bounds:', returnCells.map(cell => `${cell.x}, ${cell.y}`));
      return [];
    }

    // get a list of null cells in the board
    if (state.board && Array.isArray(state.board)) {
      let nullCells = [];
      state.board.forEach((row, cellY) => {
        if (!row.some((cell) => cell === null)) {
          return;
        }

        row.forEach((cell, cellX) => {
          if (cell === null) {
            nullCells.push({ x: cellX, y: cellY });
          }
        });
      });

      if (nullCells.length !== 0) {
        // check each returnCell against the nullCells
        let emptyCellCheck = returnCells.filter(cell => 
          nullCells.some(nullCell => nullCell.x === cell.x && nullCell.y === cell.y)
        );
        if (emptyCellCheck.length !== 0) {
          if (debug) console.error('Tetrimino intrudes on null cells:', returnCells.map(cell => `${cell.x}, ${cell.y}`));
          return [];
        }
      }
    }

    return returnCells;
  },
  sortPokedex: (state) => (pokedex) => {
    if (!pokedex || pokedex.length === 0) {
      return [];
    }

    let sorted = [...pokedex];

    switch (state.sort) {
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

  isCellSelected: (state) => (x, y) => {
    return state.selectedCells.some(cell => {
      if (cell.x !== x || cell.y !== y) return false;
      if (state.mode === 'vs') {
        const viewer = (state.viewingPlayer || state.currentPlayer)?.toLowerCase();
        return cell.username?.toLowerCase() === viewer;
      }
      return true;
    });
  },
  isCellTracked: (state) => (x, y) => {
    return state.trackedCells.some(cell => cell.x === x && cell.y === y);
  },
  isCellHovered: (state) => (x, y) => {
    return state.hoverCell.x === x && state.hoverCell.y === y;
  },

  getSelectedCellColor: (state) => (x, y) => {
    let cell;
    if (state.mode === 'vs') {
      const viewer = (state.viewingPlayer || state.currentPlayer)?.toLowerCase();
      cell = state.selectedCells?.find(c => c.x === x && c.y === y && c.username?.toLowerCase() === viewer);
    } else {
      cell = state.selectedCells?.find(c => c.x === x && c.y === y);
    }

    if (state.useTetrisColors === 'tetrimino') {
      return state.pieceDef[cell?.type]?.color || state.colors.singleSelect;
    }

    if (state.useTetrisColors === 'player' || state.useTetrisColors === true) {
      const players = Object.values(state.players);
      const player = players.find(p => p.username === cell?.username);
      return player?.color || state.colors.singleSelect;
    }

    return state.colors.singleSelect;
  },

  getRandomPiece: (state) => (index) => {
    let seed = ['piece', state.seed, state.step, index].join(':');
    if (state.mode !== 'vs' && state.currentPlayer) {
      seed = [seed, state.currentPlayer].join(':');
    }
    let rng = new RNG(seed);
    let number = rng();
    
    const types = Object.keys(state.pieceDef).filter(type => type !== '.');
    if (types.length === 0) {
      return { 
        type: '.', 
        rotation: 0 
      }; 
    }

    return { 
      type: types[Math.floor(number * types.length)], 
      rotation: state.rotation,
    };
  },

  getReverseHistory: (state) => {
    return [...state.history].reverse();
  },
};

const actions = {
  createRoom({ state }, data) {
    return new Promise((resolve, reject) => {
      PostRequest('/tetris-mp/', data, (response) => {
        if (debug) {
          console.log('Room created successfully', response);
        }
        resolve(response);
      }, (error) => {
        if (debug) {
          console.error('Failed to create room', error);
        }
        reject(error);
      });
    });
  },
  newPlayer({ commit }, data) {
    return new Promise((resolve, reject) => {
      PostRequest(`/tetris-mp/${state.uuid}/join`, data.player, (response) => {
        if (debug) {
          console.log('Joined room successfully', response);
        }
        commit('SET_PLAYERS', response.data.players);
        resolve(response);
      }, (error) => {
        if (debug) {
          console.error('Failed to join room', error);
        }
        reject(error);
      });
    });
  },
  updatePlayer({ commit }, data) {
    return new Promise((resolve, reject) => {
      PostRequest(`/tetris-mp/${state.uuid}/update-player`, {
        username: data.username,
        color: data.color
      }, (response) => {
        commit('SET_PLAYERS', response.data.players);
        resolve(response);
      }, (error) => {
        reject(error);
      });
    });
  },
  
  saveBoard({ state, commit }, { clearAll = false } = {}) {
    let saveData = new FormData();
    saveData.append('name', state.name);
    saveData.append('seed', state.seed);
    saveData.append('mode', state.mode);
    saveData.append('pokedex', JSON.stringify(state.settings.pokedex));
    saveData.append('perRow', state.settings.perRow);
    saveData.append('tetriminosToGenerate', state.settings.tetriminosToGenerate);
    saveData.append('sort', state.settings.sort);
    saveData.append('selectionType', state.settings.selectionType);
    saveData.append('perPlayer', state.settings.perPlayer);
    saveData.append('trackedCells', JSON.stringify(state.trackedCells));
    saveData.append('history', JSON.stringify(state.history));
    saveData.append('clearAll', clearAll ? 'true' : 'false');

    commit('updateField', { path: 'lastSaved', value: new Date().toISOString() });
    return new Promise((resolve, reject) => {
      PostRequest(`/tetris-mp/${state.uuid}/save`, 
        saveData, 
        (response) => {
          resolve(response);
        }, 
        (error) => {
          reject(error);
        }
      );
    });
  },

  sendNewCell({ state }, cell) {
    return new Promise((resolve, reject) => {
      PostRequest(`/tetris-mp/${state.uuid}/add-new-cell`,
        cell, 
        (response) => {
          resolve(response);
        }, (error) => {
          reject(error);
        }
      );
    });
  },

  removeLastCell({ state }) {
    return new Promise((resolve, reject) => {
      PostRequest(`/tetris-mp/${state.uuid}/remove-last-cell`, 
        (response) => {
          resolve(response);
        }, (error) => {
          reject(error);
        }
      );
    });
  },

  setBoard({ state, commit, getters }, {board, perRow}) {
    if (!Array.isArray(board)) {
      if (debug) console.error('Invalid board format provided');
      return;
    }

    // convert the pokedex to a board
    let updateBoard = getters.sortPokedex(board);
    updateBoard = updateBoard
      .reduce((acc, curr, index) => {
        if (index % perRow === 0) {
          acc.push([]);
        }
        acc[acc.length - 1 > 0 ? acc.length - 1 : 0].push(curr);

        return acc;
      }, [])
    ;

    // last row padding to ensure it meets perRow
    let lastRow = updateBoard[updateBoard.length - 1];
    if (lastRow.length < perRow) {
      while (lastRow.length < perRow) {
        lastRow.push(null);
      }
    }

    commit('updateField', { path: 'board', value: updateBoard });
    let boardLength = 0;
    updateBoard.forEach(row => {
      row.forEach(cell => {
        if (cell !== null) {
          boardLength += 1;
        }
      });
    });

    commit('updateField', { path: 'settings.perRow', value: perRow });
    commit('updateField', { path: 'settings.selectedPokedexLength', value: boardLength });
  },

  setUuid({ commit }, uuid) {
    commit('updateField', { path: 'uuid', value: uuid });
  },

  setPlayers({ commit }, players) {
    commit('SET_PLAYERS', players);
  },

  setState({ state, commit, dispatch }, data) {
    if (data.perRow) commit('updateField', { path: 'settings.perRow', value: parseInt(data.perRow) });
    if (data.tetriminosToGenerate) commit('updateField', { path: 'settings.tetriminosToGenerate', value: parseInt(data.tetriminosToGenerate) });
    if (data.pokedex) commit('updateField', { path: 'settings.pokedex', value: data.pokedex });
    if (data.sort) commit('updateField', { path: 'settings.sort', value: data.sort });
    if (data.selectionType) commit('updateField', { path: 'settings.selectionType', value: data.selectionType });
    if (data.perPlayer !== undefined) commit('updateField', { path: 'settings.perPlayer', value: data.perPlayer });

    commit('updateField', { path: 'name', value: data.name });
    commit('updateField', { path: 'seed', value: data.seed });
    commit('updateField', { path: 'mode', value: data.mode || 'blackout' });
    commit('updateField', { path: 'step', value: 0 });
    dispatch('setRNG');
    if ('trackedCells' in data) {
      commit('updateField', { path: 'trackedCells', value: data.trackedCells });
    }
    dispatch('setHistory', data.history);
  },

  setSettings({ commit }, settings) {
    if (settings.showGridCoords !== undefined) {
      commit('updateField', { path: 'showGridCoords', value: settings.showGridCoords });
    }
    if (settings.cellSize !== undefined) {
      commit('updateField', { path: 'cellSize', value: settings.cellSize });
    }
    if (settings.cellSpacing !== undefined) {
      commit('updateField', { path: 'cellSpacing', value: settings.cellSpacing });
    }
    if (settings.playerColor !== undefined) {
      commit('updateField', { path: 'playerColor', value: settings.playerColor });
    }
    if (settings.trackColor !== undefined) {
      commit('updateField', { path: 'colors.trackColor', value: settings.trackColor });
    }
    if (settings.useTetrisColors !== undefined) {
      commit('updateField', { path: 'useTetrisColors', value: settings.useTetrisColors });
    }
  },

  setHistory({ commit, dispatch }, history) {
    Object.values(history).every(item => {
      if (!item || typeof item !== 'object' || !('x' in item) || !('y' in item) || !('type' in item) || !('rotation' in item)) {
        if (debug) console.error('Invalid history item provided', item);
        return false;
      }
    });
    commit('updateField', { path: 'selectedCells', value: [] });
    commit('updateField', { path: 'history', value: [] });
    history.forEach((item) => {
      dispatch('addSelectedCell', { ...item, ignoreChecks: true });
      dispatch('addHistory', item);
      if (item?.username.toLowerCase() === state.currentPlayer?.toLowerCase()) {
        dispatch('increaseStep');
      }
    });
  },

  setViewingPlayer({ commit }, username) {
    commit('updateField', { path: 'viewingPlayer', value: username ?? null });
    commit('updateField', { path: 'selectedHistoryId', value: null });
  },

  setCurrentPlayer({ commit }, player) {
    if (typeof player !== 'string' || player.trim() === '') {
      if (debug) console.error('Invalid current player provided');
      return;
    }
    commit('updateField', { path: 'currentPlayer', value: player });
  },

  setRNG({ state, commit }) {
    let rng = new RNG(state.seed);
    // try and catch the RNG calls up...
    if (state.step > 0) {
      for (let i = 0; i < state.step -1; i++) { rng(); }
    }

    commit('updateField', { path: 'rng', value: rng });
  },

  setPieceDef({ commit }, pieceDef) {
    if (typeof pieceDef !== 'object' || Object.keys(pieceDef).length === 0) {
      if (debug) console.error('Invalid piece definition provided');
      return;
    }
    commit('updateField', { path: 'pieceDef', value: pieceDef });
  },

  setPieceSelection({ commit, state }, index) {
    if (typeof index !== 'number' || index < 0) {
      if (debug) console.error('Invalid piece selection index provided');
      return;
    }
    if (!Object.keys(state.pieceGeneration).map((key) => parseInt(key)).includes(index)) {
      if (debug) console.error('Piece selection index out of bounds');
      return;
    }
    commit('updateField', { path: 'pieceSelection', value: index });
  },

  setHoverCell({ commit }, { x, y }) {
    if (typeof x !== 'number' || typeof y !== 'number') {
      if (debug) console.error('Invalid hover cell coordinates provided');
      return;
    }
    commit('updateField', { path: 'hoverCell', value: { x: x, y: y } });
  },
  
  increaseStep({ state, commit }) {
    return new Promise((resolve) => {
      let step = state.step + 1;
      commit('updateField', { path: 'step', value: step });
      resolve(step);
    });
  },

  addHistory({ state, commit }, historyItem) {
    if (!historyItem || typeof historyItem !== 'object' || !('x' in historyItem) || !('y' in historyItem) || !('type' in historyItem)) {
      return;
    }
    if (historyItem.x < 0 || historyItem.y < 0) {
      return;
    }
    historyItem.timestamp = new Date().toISOString();
    let history = [...state.history];
    history.push(historyItem);
    commit('updateField', { path: 'history', value: history });
  },

  regeneratePieces({ state, getters, commit }) {
    if (!state.rng || !state.pieceDef) {
      if (debug) console.error('RNG or piece definition not set');
      return;
    }

    const pieces = [];
    for (let i = 0; i < state.settings.tetriminosToGenerate; i++) {
      pieces.push(getters.getRandomPiece(i));
    }
    pieces.push({ type: '.', rotation: 0 });
    if (debug) {
      pieces.push({ type: 't', rotation: 0 });
      pieces.push({ type: 'o', rotation: 0 });
      pieces.push({ type: 'i', rotation: 0 });
      pieces.push({ type: 's', rotation: 0 });
      pieces.push({ type: 'z', rotation: 0 });
      pieces.push({ type: 'j', rotation: 0 });
      pieces.push({ type: 'l', rotation: 0 });
    }
    commit('updateField', { path: 'pieceGeneration', value: pieces });
    if (state.pieceSelection >= pieces.length) {
      commit('updateField', { path: 'pieceSelection', value: 0 });
    }
  },

  rotateTetrimino({ state, commit }, rotation) {
    let newRotation;
    if (typeof rotation !== 'number') {
      newRotation = (state.rotation + 1);
    } else {
      newRotation = rotation;
    }
    if (newRotation >= 4) {
      newRotation = 0;
    }
    if (newRotation < 0) {
      newRotation = 0;
    }  
    commit('updateField', { path: 'rotation', value: newRotation });
  },

  flipTetrimino({ dispatch }) {
    dispatch('rotateTetrimino');
    dispatch('rotateTetrimino');
  },

  addSelectedCell({ state, commit, getters }, cell) {
    if (!cell || typeof cell !== 'object' || !('x' in cell) || !('y' in cell) || !('type' in cell) || !('rotation' in cell)) {
      if (debug) console.error('Invalid cell object provided', cell);
      return;
    }

    const ignoreChecks = cell.ignoreChecks === true;
    let newCells;
    if (state.settings.selectionType !== 'tetris') {
      newCells = getters.getTetriminoCoords('.', 0, cell.x, cell.y, ignoreChecks);
    } else {
      if (!state.pieceDef[cell.type]) {
        cell.type = '.';
      }
      newCells = getters.getTetriminoCoords(cell.type, cell.rotation, cell.x, cell.y, true);
    }

    let selectedCells = [...state.selectedCells];
    let trackedCells = [...state.trackedCells];
    newCells.forEach(newCell => { 
      newCell.type = cell.type || '.';
      newCell.username = cell.username;
      selectedCells.push(newCell);

      // remove from trackedCells
      if (trackedCells.findIndex(trackedCell => trackedCell.x === newCell.x && trackedCell.y === newCell.y) > -1) {
        trackedCells = trackedCells.filter(trackedCell => trackedCell.x !== newCell.x || trackedCell.y !== newCell.y);
      }
    });

    commit('updateField', { path: 'selectedCells', value: selectedCells });
    commit('updateField', { path: 'trackedCells', value: trackedCells });
  },

  toggleTrackCell({ state, commit }, cell) {
    if (!cell || typeof cell !== 'object' || !('x' in cell) || !('y' in cell)) {
      if (debug) console.error('Invalid cell object provided for tracking', cell);
      return;
    }

    let trackedCells = [...state.trackedCells];
    const index = trackedCells.findIndex(trackedCell => trackedCell.x === cell.x && trackedCell.y === cell.y);
    
    if (index > -1) {
      // Cell is already tracked, remove it
      trackedCells.splice(index, 1);
    } else {
      // Cell is not tracked, add it
      trackedCells.push({ x: cell.x, y: cell.y });
    }

    commit('updateField', { path: 'trackedCells', value: trackedCells });
  },


  clearBoard({ commit, dispatch }) {
    commit('updateField', { path: 'selectedCells', value: [] });
    commit('updateField', { path: 'trackedCells', value: [] });
    commit('updateField', { path: 'hoverCell', value: { x: -1, y: -1 } });
    commit('updateField', { path: 'history', value: [] });
    commit('updateField', { path: 'pieceSelection', value: 0 });
    commit('updateField', { path: 'rotation', value: 0 });
    commit('updateField', { path: 'pieceGeneration', value: [] });
    commit('updateField', { path: 'step', value: 0 });
    commit('updateField', { path: 'viewingPlayer', value: null });
    dispatch('setRNG');
    dispatch('regeneratePieces');
    return dispatch('saveBoard', { clearAll: true });
  },

  undoLastAction({ state, commit, dispatch, getters }) {
    if (state.history.length === 0) {
      return;
    }

    let history = [...state.history];
    let lastAction = history.pop();
    if (!lastAction || lastAction.username !== state.currentPlayer) {
      return;
    }

    commit('updateField', { path: 'history', value: history });

    commit('updateField', { path: 'hoverCell', value: { x: lastAction.x, y: lastAction.y } });
    let selectedCells = [...state.selectedCells];
    commit('updateField', { path: 'selectedCells', value: [] });

    let lastActionCells = getters.getTetriminoCoords(lastAction.type, lastAction.rotation, lastAction.x, lastAction.y);
    // remove the selected cells from the board
    lastActionCells.forEach(cell => {
      selectedCells = selectedCells.filter(selectedCell => !(selectedCell.x === cell.x && selectedCell.y === cell.y));
    });
    commit('updateField', { path: 'selectedCells', value: selectedCells });

    // decrement step
    if (state.step > 0) {
      commit('updateField', { path: 'step', value: state.step - 1 });
    }

    // regenerate pieces
    dispatch('setRNG');
    dispatch('regeneratePieces');
    dispatch('removeLastCell');
  },
};

const mutations = {
  updateField,
  SET_PLAYERS: (state, players) => {
    if (players !== undefined && players !== null) {
      state.players = players;
    }
  }
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
