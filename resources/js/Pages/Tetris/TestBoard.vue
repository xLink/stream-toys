<template>
  <Layout>
    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-4">
      <h1 class="text-4xl font-bold text-center">Tetris Test Boards</h1>
    </div>

    <div v-if="!shape" class="flex flex-col items-center gap-4 p-8">
      <p class="text-lg text-slate-300">Select a shape to view:</p>
      <div class="flex gap-4">
        <a
          v-for="s in shapes"
          :key="s"
          :href="`/tetris/test/${s}`"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-lg font-bold capitalize transition-colors"
        >
          {{ s }}
        </a>
      </div>
    </div>

    <div v-else class="flex flex-col w-full p-4 gap-2">
      <div class="flex items-center gap-4 mb-2">
        <a href="/tetris/test" class="px-3 py-1 bg-slate-600 hover:bg-slate-500 text-white rounded text-sm">← All Shapes</a>
        <h2 class="text-2xl font-bold capitalize">{{ shape }}</h2>
        <span class="text-slate-400 text-sm">{{ shapeCellCount }} cells</span>
        <button
          @click="shuffleSeed"
          class="px-3 py-1 bg-purple-600 hover:bg-purple-500 text-white rounded text-sm font-bold transition-colors"
        >
          Shuffle
        </button>
        <span class="text-slate-500 text-xs">seed: {{ currentSeed }}</span>
        <div class="flex gap-2 ml-auto">
          <a
            v-for="s in shapes"
            :key="s"
            :href="`/tetris/test/${s}`"
            class="px-3 py-1 rounded text-sm transition-colors capitalize"
            :class="s === shape ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
          >
            {{ s }}
          </a>
        </div>
      </div>

      <div class="flex flex-col border border-white">
        <div class="flex flex-row gap-2 p-2 bg-slate-800 text-sm">
          <div class="flex gap-1 items-center">
            <span class="w-5 h-5 rounded bg-green-700 border border-slate-500"></span>
            {{ selectedCellCount }} Caught
          </div>
          <div class="flex gap-1 items-center">
            <span class="w-5 h-5 rounded bg-black border border-slate-500"></span>
            {{ shapeCellCount - selectedCellCount }} Uncaught
          </div>
        </div>

        <div id="pokeboard" class="flex flex-col w-full p-2 overflow-auto scrollbar-thin">
          <div class="flex flex-row gap-[--extraPadding]" v-if="showGridCoords"
            :style="{ '--extraPadding': (cellSpacing / 4) + 'rem' }"
          >
            <div class="flex justify-center items-center" :style="{ minWidth: (cellSize / 2) + 'px' }"></div>
            <div
              v-for="i in perRow"
              :key="'h' + i"
              class="flex justify-center items-center"
              :style="{ minWidth: cellSize + 'px' }"
            >
              {{ (i - 1).toString().padStart(2, '0') }}
            </div>
          </div>

          <div
            v-for="(row, y) in renderCells"
            :key="y"
            class="flex flex-row gap-[--extraPadding]"
            :data-row="y"
            :style="{ '--extraPadding': (cellSpacing / 4) + 'rem' }"
          >
            <div v-if="showGridCoords" class="flex justify-center items-center" :style="{ minWidth: (cellSize / 2) + 'px' }">
              {{ y.toString().padStart(2, '0') }}
            </div>

            <div
              v-for="(cell, x) in row"
              :key="x + ',' + y"
              class="flex rounded transition-all duration-200 ease-in-out"
              :class="{ 'invisible': !cell.inShape }"
              :style="{
                minWidth: cellSize + 'px',
                height: cellSize + 'px',
                backgroundColor: cellBg(cell, x, y),
                borderWidth: '1px',
                borderColor: isHovered(x, y) ? '#FF00EC' : '#374151',
              }"
              :data-x="x"
              :data-y="y"
              :data-testid="cell.inShape ? `cell-${x}-${y}` : undefined"
              :data-pokemon="cell.name || ''"
              @click="cell.inShape && toggleCell(x, y)"
              @mouseover="hoverX = x; hoverY = y"
              @mouseleave="hoverX = -1; hoverY = -1"
            >
              <img
                v-if="cell.image"
                :src="cell.image"
                :alt="cell.name"
                class="w-full h-full object-contain"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'TetrisTestBoard',

  props: {
    pokedexData: { type: [Object, Array], required: true },
    shape: { type: String, default: null },
    shapes: { type: Array, required: true },
    perRow: { type: Number, default: 15 },
    seed: { type: Number, default: 42 },
  },

  data() {
    return {
      hoverX: -1,
      hoverY: -1,
      showGridCoords: true,
    };
  },

  created() {
    if (this.shape) {
      this.$store.commit('tetrisTest/updateField', { path: 'shape', value: this.shape });
      this.$store.commit('tetrisTest/updateField', { path: 'perRow', value: this.perRow });
      this.$store.commit('tetrisTest/updateField', { path: 'seed', value: this.seed });
      this.$store.commit('tetrisTest/updateField', { path: 'selectedCells', value: [] });
      this.$store.dispatch('tetrisTest/generateBoard', this.pokedexData);
    }
  },

  computed: {
    ...mapFields('tetrisTest', ['board', 'cellSize', 'cellSpacing', 'selectedCells', 'colors']),

    shapeMask() {
      return this.$store.getters['tetrisTest/shapeMask'];
    },

    shapeCellCount() {
      return this.shapeMask.mask.length;
    },

    currentSeed() {
      return this.$store.state.tetrisTest.seed;
    },

    selectedCellCount() {
      return this.selectedCells.length;
    },

    maskSet() {
      return new Set(this.shapeMask.mask.map(c => `${c.x},${c.y}`));
    },

    renderCells() {
      if (!this.board || this.board.length === 0) return [];

      return this.board.map((row, y) =>
        row.map((pokemon, x) => {
          const inShape = this.maskSet.has(`${x},${y}`);
          return {
            ...(pokemon || {}),
            inShape,
          };
        })
      );
    },
  },

  methods: {
    cellBg(cell, x, y) {
      if (!cell.inShape) return 'transparent';
      if (this.$store.getters['tetrisTest/isCellSelected'](x, y)) return this.colors.singleSelect;
      return this.colors.background;
    },

    isHovered(x, y) {
      return this.hoverX === x && this.hoverY === y;
    },

    shuffleSeed() {
      const newSeed = Math.floor(Math.random() * 100000);
      this.$store.commit('tetrisTest/updateField', { path: 'seed', value: newSeed });
      this.$store.commit('tetrisTest/updateField', { path: 'selectedCells', value: [] });
      this.$store.dispatch('tetrisTest/generateBoard', this.pokedexData);
    },

    toggleCell(x, y) {
      this.$store.dispatch('tetrisTest/selectCell', { x, y });
    },
  },
};
</script>
