<template>
  <Layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50">
      <MenuOpenIcon v-if="showOptions" class="text-white" @click="showOptions = !showOptions" />
      <MenuCloseIcon v-if="!showOptions" class="text-white" @click="showOptions = !showOptions" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex gap-x-2">
        <Tetrimino :type="headerTetris[0]?.type" :rotation="headerTetris[0]?.rotation" />
        <Tetrimino :type="headerTetris[1]?.type" :rotation="headerTetris[1]?.rotation" />
      </div>
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
      </div>
      <div class="flex gap-x-2">
        <Tetrimino :type="headerTetris[2]?.type" :rotation="headerTetris[2]?.rotation" />
        <Tetrimino :type="headerTetris[3]?.type" :rotation="headerTetris[3]?.rotation" />
      </div>
    </div>

    <div class="flex flex-col w-full p-4">
      <Options v-show="showOptions" />
      <Board :pokedexData="pokedexData" />
    </div>
  </Layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';

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
      headerTetris: [{}, {}, {}, {}],
      rng: null,
    }
  },

  created() {
    this.rng = RNG(this.seed);
    this.headerTetris[0] = this.pickRandomTetrimino();
    this.headerTetris[1] = this.pickRandomTetrimino();
    this.headerTetris[2] = this.pickRandomTetrimino();
    this.headerTetris[3] = this.pickRandomTetrimino();
  },

  methods: {
    pickRandomTetrimino() {
      const types = Object.keys(this.pieceDef).filter(type => type !== '.');
      const randomType = types[Math.floor(this.rng() * types.length)];
      const randomRotation = Math.floor(this.rng() * 4);
      return { type: randomType, rotation: randomRotation };
    },
  },

  computed: {
    ...mapFields('tetris', ['seed', 'pieceDef']),
  },
};
</script>