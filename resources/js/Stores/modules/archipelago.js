import { getField, updateField } from 'vuex-map-fields';
import ArchipelagoClient from '@/Helpers/Archipelago.js';

const STORAGE_KEY = 'archipelago_connection';
const EVENTS_KEY = 'archipelago_events';

const GAME_TO_POKEDEX = {
  'pokemon red': ['gen1'],
  'pokemon blue': ['gen1'],
  'pokemon red and blue': ['gen1'],
  'pokemon yellow': ['gen1'],
  'pokemon gold': ['gen1', 'gen2'],
  'pokemon silver': ['gen1', 'gen2'],
  'pokemon crystal': ['gen1', 'gen2'],
  'pokemon ruby': ['gen1', 'gen2', 'gen3'],
  'pokemon sapphire': ['gen1', 'gen2', 'gen3'],
  'pokemon emerald': ['gen1', 'gen2', 'gen3'],
  'pokemon firered': ['gen1'],
  'pokemon leafgreen': ['gen1'],
  'pokemon firered and leafgreen': ['gen1'],
  'pokemon diamond': ['gen1', 'gen2', 'gen3', 'gen4'],
  'pokemon platinum': ['gen1', 'gen2', 'gen3', 'gen4'],
  'pokemon heartgold': ['gen1', 'gen2', 'gen3', 'gen4'],
  'pokemon soulsilver': ['gen1', 'gen2', 'gen3', 'gen4'],
  'pokemon black': ['gen1', 'gen2', 'gen3', 'gen4', 'gen5'],
  'pokemon white': ['gen1', 'gen2', 'gen3', 'gen4', 'gen5'],
  'pokemon black 2': ['gen1', 'gen2', 'gen3', 'gen4', 'gen5'],
  'pokemon white 2': ['gen1', 'gen2', 'gen3', 'gen4', 'gen5'],
};

function loadJson(key) {
  try {
    const raw = localStorage.getItem(key);
    if (raw) return JSON.parse(raw);
  } catch {}
  return {};
}

const saved = loadJson(STORAGE_KEY);
const savedEvents = loadJson(EVENTS_KEY);

const state = {
  hostname: saved.hostname || 'archipelago.gg',
  port: saved.port || 38281,
  slotName: saved.slotName || '',
  password: saved.password || '',
  status: 'disconnected',
  lastError: '',
  log: [],
  caught: savedEvents.caught || [],
  seen: savedEvents.seen || [],
  lastSeen: savedEvents.lastSeen || '',
};

let client = null;

const getters = {
  getField,
  isConnected: (state) => state.status === 'connected',
};

const mutations = {
  updateField,
};

const actions = {
  connect({ state, dispatch, commit }) {
    if (!client) {
      client = new ArchipelagoClient();
      client.onStatusChange = (status, error) => {
        dispatch('setStatus', { status, error });
      };
      client.onMessage = (message) => {
        dispatch('logMessage', message);
      };
      client.onLocationChecked = ({ pokemon, type }) => {
        dispatch('pokemonChecked', { pokemon, type });
      };
      client.onSeedName = (seedName) => {
        dispatch('applySeed', seedName);
      };
      client.onGameDetected = (game) => {
        dispatch('applyGamePokedex', game);
      };
    }
    dispatch('saveConnection');
    client.connect(state.hostname, state.port, state.slotName, state.password);
  },

  disconnect() {
    if (client) {
      client.disconnect();
    }
  },

  cleanup() {
    if (client) {
      client.disconnect();
      client = null;
    }
  },

  setStatus({ commit }, { status, error }) {
    commit('updateField', { path: 'status', value: status });
    commit('updateField', { path: 'lastError', value: error || '' });
  },

  logMessage({ state, commit }, message) {
    let timestamp = new Date().toLocaleTimeString();
    let log = [...state.log, `[${timestamp}] ${message}`];
    if (log.length > 500) {
      log = log.slice(-500);
    }
    commit('updateField', { path: 'log', value: log });
  },

  clearLog({ commit }) {
    commit('updateField', { path: 'log', value: [] });
  },

  applyGamePokedex({ dispatch }, game) {
    const key = game.toLowerCase();
    const pokedex = GAME_TO_POKEDEX[key];
    if (pokedex) {
      dispatch('tetris2/setPokedex', pokedex, { root: true });
      dispatch('tetris2/saveBoard', null, { root: true });
    }
  },

  applySeed({ dispatch }, seedName) {
    let hash = 0;
    for (let i = 0; i < seedName.length; i++) {
      hash = ((hash << 5) - hash + seedName.charCodeAt(i)) | 0;
    }
    const seed = Math.abs(hash) % 1000000;
    dispatch('tetris2/setSeed', seed, { root: true });
    dispatch('tetris2/setRNG', null, { root: true });
    dispatch('tetris2/regeneratePieces', null, { root: true });
    dispatch('tetris2/saveBoard', null, { root: true });
  },

  pokemonChecked({ state, commit, dispatch }, { pokemon, type }) {
    const lower = pokemon.toLowerCase();
    if (type === 'caught') {
      if (!state.caught.some(p => p.toLowerCase() === lower)) {
        commit('updateField', { path: 'caught', value: [...state.caught, pokemon] });
      }
    } else if (type === 'seen') {
      if (!state.seen.some(p => p.toLowerCase() === lower)) {
        commit('updateField', { path: 'seen', value: [...state.seen, pokemon] });
      }
      commit('updateField', { path: 'lastSeen', value: pokemon });
      dispatch('tetris2/trackByName', pokemon, { root: true });
    }
    dispatch('saveEvents');
  },

  saveEvents({ state }) {
    try {
      localStorage.setItem(EVENTS_KEY, JSON.stringify({
        caught: state.caught,
        seen: state.seen,
        lastSeen: state.lastSeen,
      }));
    } catch {}
  },

  saveConnection({ state }) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        hostname: state.hostname,
        port: state.port,
        slotName: state.slotName,
        password: state.password,
      }));
    } catch {}
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
