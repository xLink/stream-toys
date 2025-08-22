import store from '@/Stores/index.js';

export const pickRandomTetrimino = () => {
  store.dispatch('tetris2/increaseStep');

  return store.getters['tetris2/getRandomPiece']();
};