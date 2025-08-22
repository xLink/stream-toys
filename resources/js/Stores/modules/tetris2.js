import { getField, updateField } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';

const debug = !true;

const state = {
  name: 'Slot 1',
  seed: null,
  rng: null,
  step: 0,

  pokedex: ['gen1'],
  selectedPokedexLength: 151,
  board: [],
  perRow: 20,
  sort: 'random',
  cellSize: 60,
  cellSpacing: 1,
  
  history: [],
  showHistory: 1,  
  showGridCoords: 1,
  selectedCells: [],
  trackedCells: [],
  hoverCell: { x: -1, y: -1 },

  colors: {
    background: '#000000',
    hoverBorder: '#FF00EC',
    singleSelect: '#138e57',
    trackColor: '#133437',
  },

  selectionType: 'tetris',
  useTetrisColors: 1,
  tetriminosToGenerate: 2,
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
};

const getters = {
  getField,
  settingsStr: (state) => {
    return JSON.stringify(state);
  },
  step: (state) => {
    return state.step;
  },
  isCellSelected: (state) => (x, y) => {
    let cells = [];

    state.selectedCells.forEach((cell) => {
      if (cell.x === x && cell.y === y) {
        cells.push(cell);
      }
    });

    return cells.length > 0;
  },
  isCellTracked: (state) => (x, y) => {
    return state.trackedCells.some(cell => cell.x === x && cell.y === y);
  },
  isCellHovered: (state) => (x, y) => {
    return state.hoverCell.x === x && state.hoverCell.y === y;
  },
  getSelectedCellColor: (state) => (x, y) => {
    if (parseInt(state.useTetrisColors) !== 1) {
      return state.colors.singleSelect;
    }

    const cell = state.selectedCells?.find(cell => cell.x === x && cell.y === y);
    if (cell?.type === '.') {
      return state.colors.singleSelect;
    }

    const cellType = state.pieceDef[cell?.type];
    return cellType?.color ?? state.colors.singleSelect;
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
  getRandomPiece: (state) => (index) => {
    let rng = new RNG('piece' + state.seed + state.step + index);
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
  selectedPiece: (state) => {
    if (state.pieceGeneration.length === 0) {
      return { type: '.', rotation: state.rotation };
    }
    return state.pieceGeneration[state.pieceSelection] || { type: '.', rotation: state.rotation };
  },
  getTetriminoCoords: (state, getters) => (type, rotation, x, y, ignoreChecks=false) => {
    if (state.selectionType !== 'tetris') {
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
      if (debug) console.error('Hover cell is out of bounds:', state.hoverCell);
      return [];
    }

    // console.info('Hover cell x:', state.hoverCell.x, 'perRow:', state.perRow, state.hoverCell.x > state.perRow)
    if (state.hoverCell.x > state.perRow) {
      if (debug) console.error('Hover cell is out of bounds:', state.hoverCell.x, state.perRow);
      return [];
    }

    let rowLength = (state.selectedPokedexLength / state.perRow);
    // console.info('Hover cell y:', state.hoverCell.y, 'rowLength:', rowLength, state.hoverCell.y >= rowLength);
    if (state.hoverCell.y >= rowLength) {
      if (debug) console.error('Hover cell is out of bounds:', state.hoverCell.y, (state.selectedPokedexLength / state.perRow));
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
    if (returnCells.some(cell => cell.x < 0 || cell.y < 0 || cell.x >= state.perRow || cell.y >= (state.selectedPokedexLength / state.perRow))) {
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
  getReverseHistory: (state) => {
    return [...state.history].reverse();
  },
};

const actions = {
  setSeed({ commit }, seed) {
    if (typeof seed !== 'number' || seed < 0) {
      if (debug) console.error('Invalid seed value provided');
      return;
    }

    commit('updateField', { path: 'seed', value: seed });
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

  setPieceSelection({ commit }, index) {
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

  setSelectedPokedexLength({ commit }, length) {
    if (typeof length !== 'number' || length < 0) {
      if (debug) console.error('Invalid selected Pokedex length provided');
      return;
    }
    commit('updateField', { path: 'selectedPokedexLength', value: length });
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
    for (let i = 0; i < state.tetriminosToGenerate; i++) {
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

  setSettings({ state, commit }, settings) {
    if (typeof settings !== 'object') {
      if (debug) console.error('Settings must be an object');
      return;
    }

    if (Object.keys(settings).length === 0) {
      console.warn('No settings provided to update');
      return;
    }

    Object.keys(state).forEach((key) => {
      if (settings.hasOwnProperty(key)) {
        commit('updateField', { 
          path: key, 
          value: settings[key] 
        });
      }
    });
  },

  addSelectedCell({ state, commit, getters }, cell) {
    if (!cell || typeof cell !== 'object' || !('x' in cell) || !('y' in cell) || !('type' in cell) || !('rotation' in cell)) {
      if (debug) console.error('Invalid cell object provided', cell);
      return;
    }

    let newCells;
    if (state.selectionType !== 'tetris') {
      newCells = getters.getTetriminoCoords('.', 0, cell.x, cell.y);
    } else {
      if (!state.pieceDef[cell.type]) {
        cell.type = '.';
      }
      newCells = getters.getTetriminoCoords(cell.type, cell.rotation, cell.x, cell.y);
    } 

    let selectedCells = [...state.selectedCells];
    newCells.forEach(newCell => { 
      newCell.type = cell.type || '.';
      selectedCells.push(newCell);
    });

    commit('updateField', { path: 'selectedCells', value: selectedCells });
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

  generateBoard({ state, commit, getters, dispatch }, pokedexData) {
    if (!pokedexData || !state.pokedex || state.pokedex.length === 0) {
      return [];
    }

    let dexByGen = {
      gen1: [0, 151],
      gen2: [151, 251],
      gen3: [251, 386],
      gen4: [386, 493],
      gen5: [493, 649],
      gen6: [649, 721],
      gen7: [721, 809],
      gen8: [809, 905],
      gen9: [905, 1025],
    };

    // Filter dexByGen based on state.pokedex
    let selectedGens = state?.pokedex.map(gen => {
        if (!dexByGen[gen]) {
          console.warn(`Unknown generation: ${gen}`);
          return [];
        }

        return Object.values(pokedexData).slice(dexByGen[gen][0], dexByGen[gen][1]);
      })
      .flat()
    ;
    dispatch('setSelectedPokedexLength', selectedGens.length);

    // Sort the selected pokedex based on the selected sort option
    selectedGens = getters.sortPokedex(selectedGens);
    selectedGens = selectedGens
      .reduce((acc, curr, index) => {
        if (index % state.perRow === 0) {
          acc.push([]);
        }
        acc[acc.length - 1 > 0 ? acc.length - 1 : 0].push(curr);

        return acc;
      }, [])
    ;

    // last row padding to ensure it meets perRow
    let lastRow = selectedGens[selectedGens.length - 1];
    if (lastRow.length < state.perRow) {
      while (lastRow.length < state.perRow) {
        lastRow.push(null);
      }
    }

    commit('updateField', { path: 'board', value: selectedGens });
  },

  saveBoard({ state, commit }) {
    let saveData = {...state };
    delete saveData.rng; // Remove RNG from save data
    delete saveData.selectedCells;
    
    saveData = JSON.stringify(saveData);
    saveData = btoa(saveData);

    localStorage.setItem('tetris2_save', saveData);
  },

  loadBoard({ state, commit, dispatch }) {
    let saveData = localStorage.getItem('tetris2_save');
    if (saveData) {
      saveData = atob(saveData);
      saveData = JSON.parse(saveData);
      
      let keys = Object.keys(state);

      // restore the data from the save to the state
      Object.keys(saveData).forEach((key) => {
        let values = saveData[key];
        if (keys.includes(key)) {
          commit('updateField', { path: key, value: values });
        }
      });

      // reinitialize the RNG
      dispatch('setRNG');
      dispatch('regeneratePieces');
      dispatch('setPieceSelection', 0);

      // regenerate the pieces
      saveData.history.forEach((item) => {
        dispatch('addSelectedCell', item);
      });
    } else {
      throw new Error('No saved board found');
    }
  },

  clearBoard({ commit, dispatch }) {
    commit('updateField', { path: 'selectedCells', value: [] });
    commit('updateField', { path: 'trackedCells', value: [] });
    commit('updateField', { path: 'hoverCell', value: { x: -1, y: -1 } });
    commit('updateField', { path: 'history', value: [] });
    commit('updateField', { path: 'step', value: 0 });
    commit('updateField', { path: 'pieceSelection', value: 0 });
    commit('updateField', { path: 'rotation', value: 0 });
    commit('updateField', { path: 'pieceGeneration', value: [] });
    dispatch('setRNG');
    dispatch('regeneratePieces');
  },

  setDefaults({ commit, dispatch }) {
    let defaultState = {
      name: 'Slot 1',
      rng: null,
      step: 0,
      pokedex: ['gen1'],
      selectedPokedexLength: 151,
      board: [],
      perRow: 20,
      sort: 'random',
      cellSize: 60,
      cellSpacing: 1,
      history: [],
      showHistory: 1,
      showGridCoords: 1,
      selectedCells: [],
      trackedCells: [],
      hoverCell: { x: -1, y: -1 },
      colors: {
        background: '#000000',
        hoverBorder: '#FF00EC',
        singleSelect: '#138e57',
        trackColor: '#133437',
      },
      selectionType: 'tetris',
      useTetrisColors: 1,
      tetriminosToGenerate: 2,
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
    };

    // reset the state to default values
    Object.keys(defaultState).forEach((key) => {
      commit('updateField', { path: key, value: defaultState[key] });
    });
    dispatch('setSeed', Math.floor(Math.random() * 1000000));
    dispatch('setRNG');
    dispatch('regeneratePieces');
    dispatch('clearBoard');

    dispatch('saveBoard');
  },

  undoLastAction({ state, commit, dispatch, getters }) {
    if (state.history.length === 0) {
      return;
    }

    let history = [...state.history];
    let lastAction = history.pop();
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
  }
};

const mutations = {
  updateField,
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
