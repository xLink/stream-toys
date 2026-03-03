import { getField, updateField } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';
import PostRequest from '@/Helpers/API/PostRequest';

const debug = !true;

const state = {
  name: '',
  seed: null,
  mode: null,
  room_creator: null,
  
  rng: null,
  players: {
    /*{name: PPN, color: 'orange'} */
  },
  settings: {
    perRow: 10,
    tetriminosToGenerate: 3,
    pokedex: ['gen1'],
  },
  color: 'blue',
  cellSize: 30,
  cellSpacing: 2,
};

const getters = {
  getField,
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
      PostRequest(`/tetris-mp/${data.room}/join`, data.player, (response) => {
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
  setPlayers({ commit }, players) {
    commit('SET_PLAYERS', players);
  },
  setState({ commit }, state) {
    commit('SET_STATE', state);
  },
};

const mutations = {
  updateField,
  SET_PLAYERS: (state, players) => {
    state.players = {
      ...state.players, 
      ...players
    };
  },
  SET_STATE: (state, newState) => {
    state.name = newState.name;
    state.seed = newState.seed;
    state.mode = newState.mode;
    state.rng = RNG(newState.seed);
  }
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
