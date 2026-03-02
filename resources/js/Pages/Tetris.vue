<template>
  <Layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50">
      <MenuOpenIcon v-if="showOptions" class="text-white" @click="showOptions = !showOptions" />
      <MenuCloseIcon v-if="!showOptions" class="text-white" @click="showOptions = !showOptions" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
      </div>
    </div>

    <div class="flex flex-col w-full p-4 gap-2">
      <Options v-show="showOptions" />
      <Board :pokedexData="pokedexData" />
    </div>
  </Layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'TetrisIndex',

  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      showOptions: false,
      username: '',
    }
  },

  created() {
    let localData = localStorage.getItem('tetris2_save');

    // if we have some local data, try to load it
    let loaded = false;
    if (localData?.trim() !== '') {
      try {
        this.$store.dispatch('tetris2/loadBoard');
        loaded = true;
      } catch (e) {
        // console.error('Failed to parse saved Tetris data:', e);
      }
    }

    if (!loaded) {
      if (this.seed === 0) {
        this.$store.dispatch('tetris2/setSeed', Math.floor(Math.random() * 1000000));
      }
      if (this.rng === null) {
        this.$store.dispatch('tetris2/setRNG');
      }
      if (this.defaultTetriminos && Object.keys(this.pieceDef).length === 0) {
        this.$store.dispatch('tetris2/setPieceDef', this.defaultTetriminos);
      }
    }
  },
  
  computed: {
    ...mapFields('app', ['defaultTetriminos']),
    ...mapFields('tetris2', [
      'seed', 
      'rng', 
      'pieceDef',
    ]),
  },
};
</script>