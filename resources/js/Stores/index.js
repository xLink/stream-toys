import { createStore } from 'vuex';
import app from '@/Stores/modules/app.js';
import tetris2 from '@/Stores/modules/tetris2.js';

const debug = process.env.NODE_ENV !== 'production';

export default createStore({
  modules: {
    app,
    tetris2,
  },
  strict: debug,
});
