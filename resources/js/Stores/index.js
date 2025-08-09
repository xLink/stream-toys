import { createStore } from 'vuex';
import tetris from '@/Stores/modules/tetris.js';

const debug = process.env.NODE_ENV !== 'production';

export default createStore({
  modules: {
    tetris,
  },
  strict: debug,
});
