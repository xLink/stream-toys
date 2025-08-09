import { getField, updateField } from 'vuex-map-fields';

const state = {
  step: 0,
  seed: Math.floor(Math.random() * 1000000),
  selectionType: 'tetris',
  tetriminosToGenerate: 2,
  pokedex: ['gen1'],
  perRow: 20,
  sort: 'random',
  cellSize: 60,
  colors: {
    background: '#000000',
    text: '#FFFFFF',
    border: '#00ECFF',
    hoverBorder: '#FF00EC',
  },
  colorGrid: 'no',
  selectedPokedex: [],
  selectedCells: [],
  pieceDef: {
    'i': { 
      blocks: [0x0F00, 0x2222, 0x00F0, 0x4444], 
      start: [[0, 1], [2, 0], [0, 2], [1, 0]], 
      color: '#93c5fd'  
    },
    'j': { 
      blocks: [0x44C0, 0x8E00, 0x6440, 0x0E20], 
      start: [[1, 2], [0, 1], [1, 0], [2, 1]], 
      color: '#0ea5e9'  
    },
    'l': { 
      blocks: [0x4460, 0x0E80, 0xC440, 0x2E00], 
      start: [[1, 2], [0, 1], [1, 0], [2, 1]], 
      color: '#f97316' 
    },
    'o': { 
      blocks: [0xCC00, 0xCC00, 0xCC00, 0xCC00], 
      start: [[0, 0], [0, 0], [0, 0], [0, 0]], 
      color: '#eab308' 
    },
    's': { 
      blocks: [0x06C0, 0x8C40, 0x6C00, 0x4620], 
      start: [[1, 1], [0, 1], [1, 0], [1, 1]], 
      color: '#22c55e'  
    },
    'z': { 
      blocks: [0x0C60, 0x4C80, 0xC600, 0x2640], 
      start: [[1, 1], [1, 1], [1, 0], [2, 1]], 
      color: '#ef4444'  
    },
    't': { 
      blocks: [0x0E40, 0x4C40, 0x4E00, 0x4640], 
      start: [[1, 1], [1, 1], [1, 1], [1, 1]], 
      color: '#a855f7' 
    },
    '.': { 
      blocks: [0x8000, 0x8000, 0x8000, 0x8000], 
      start: [[0, 0], [0, 0], [0, 0], [0, 0]], 
      color: '#6b7280'  
    },
  },
};

const getters = {
  getField,
  settingsStr: (state) => {
    return JSON.stringify(state);
  },
};

const actions = {
  setSettings({ state, commit }, settings) {
    if (typeof settings !== 'object') {
      console.error('Settings must be an object');
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
  addSelectedCell({ state, commit }, cell) {
    if (!cell || typeof cell !== 'object' || !('x' in cell) || !('y' in cell) || !('color' in cell)) {
      console.error('Invalid cell object provided');
      return;
    }

    const newCell = {
      x: cell.x,
      y: cell.y,
      color: cell.color,
    };

    let selectedCells = [...state.selectedCells];
    selectedCells.push(newCell);
    commit('updateField', { path: 'selectedCells', value: selectedCells });
  },
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
