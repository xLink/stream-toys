import { createStore } from 'vuex';
import app from '@/Stores/modules/app.js';
import tetris2 from '@/Stores/modules/tetris2.js';
import tetrismp from '@/Stores/modules/tetrismp.js';
import archipelago from '@/Stores/modules/archipelago.js';
import battleships from '@/Stores/modules/battleships.js';
import tetrisTest from '@/Stores/modules/tetrisTest.js';

const debug = process.env.NODE_ENV !== 'production';

export default createStore({
  modules: {
    app,
    tetris2,
    tetrismp,
    archipelago,
    battleships,
    tetrisTest,
  },
  strict: debug,
});
