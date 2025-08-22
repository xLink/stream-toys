<template> 
  <div class="flex flex-col border border-white">
    <div class="flex w-full bg-slate-800 p-2 gap-2 items-center">
      <div 
        class="flex flex-col w-32" 
      >
        <div class="flex">Seed: {{ seed }}</div>
        <div class="flex">Step: {{ step }}</div>
        <div class="flex">Caught: {{ selectedCells.length }} / {{ selectedPokedexLength }}</div>
        <div class="flex">Unknown: {{ selectedPokedexLength - selectedCells.length - trackedCells.length }}</div>
        <div class="flex">Hover: ({{ hoverCell.x.toString().padStart(2, '0') }}, {{ hoverCell.y.toString().padStart(2, '0') }})</div>
      </div>

      <div 
        v-if="selectionType === 'tetris'"
        class="flex flex-row gap-2" 
      >
        <Tetrimino
          v-for="(piece, index) in pieceGeneration"
          :key="index"
          :type="piece.type"
          :rotation="rotation === null ? piece.rotation : rotation"
          :active="pieceSelection === index"
          @click="$store.dispatch('tetris2/setPieceSelection', index)"
        />
      </div>

      <div class="flex flex-row text-3xl ml-auto">
        <RefreshIcon title="Rotate Tetriminos" class="cursor-pointer" @click="$store.dispatch('tetris2/rotateTetrimino')" />
        <SaveIcon title="Save Tetriminos" class="cursor-pointer text-green-500" @click="$store.dispatch('tetris2/saveBoard')" />
        <DeleteIcon title="Clear Board" class="cursor-pointer text-red-500" @click="clearBoard" /> 
      </div>

    </div>

    <div class="flex flex-row overflow-auto scrollbar-thin"
      :style="{
        '--calcHeight': 'calc((var(--cellSize) * (var(--rows) + 1)) + var(--cellSpacing) + (var(--extraPadding) * 3))',
        '--rows': Math.ceil(selectedPokedexLength / perRow),
        '--cellSize': cellSize + 'px',
        '--extraPadding': (cellSpacing / 4) + 'rem',
        '--cellSpacing': 'calc(var(--extraPadding) * var(--rows))',
      }"
    >
      <div 
        id="pokeboard" 
        class="flex flex-col w-full p-2 overflow-auto scrollbar-thin max-h-[--calcHeight]"
      >
        <div class="flex flex-col gap-[--extraPadding]" @contextmenu.prevent="() => {}">
          <div v-if="parseInt(showGridCoords) === 1" class="flex flex-row gap-[--extraPadding] ml-8">
            <div 
              v-for="i in perRow"
              class="flex justify-center items-center h-[--height] w-[--width]" 
              :style="{ '--height': (cellSize / 2) + 'px', '--width': cellSize + 'px' }"
            >
              {{ (i - 1) ?.toString().padStart(2, '0') }}
            </div>
          </div>
          <div v-for="(col, y) in renderCells"
            class="flex flex-row gap-[--extraPadding]"
            :data-row="y"
          >
            <div v-if="parseInt(showGridCoords) === 1" class="flex justify-center items-center w-[--cellSize]" :style="{ '--cellSize': (cellSize / 2) + 'px' }">
              {{ y.toString().padStart(2, '0') }}
            </div>

            <div v-for="(pokemon, x) in col"
              class="flex rounded min-w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor] transition-all duration-200 ease-in-out"
              :key="pokemon.key" 
              :class="pokemon.class"
              :style="pokemon.style"
              :data-x="x"
              :data-y="y"
            >
              <img
                v-if="typeof pokemon.image !== 'undefined'"
                :src="pokemon?.image"
                :alt="pokemon?.name"
                class="w-full h-full object-contain"
                @click="selectCell(x, y)"
                @contextmenu.prevent="trackCell(x, y)"
                @mouseover="$store.dispatch('tetris2/setHoverCell', { x: parseInt(x), y: parseInt(y) })"
              />
              <div v-else>&nbsp;</div>
            </div>
          </div>
        </div>
      </div>

      <div 
        ref="history"
        class="flex flex-col grow-0 w-2/12 p-2 bg-slate-800 text-white overflow-auto scrollbar-thin max-h-[--calcHeight] transition-all duration-200 ease-in-out"
        :class="{
          '!w-0': showHistory === '0',
        }"
        @mouseleave="() => { selectedHistoryId = null; }"
      >
        <div class="flex">
          <p>History:</p>
        </div>
        <ul>
          <li 
            v-for="(item, index) in getReverseHistory" 
            :key="index"
            class="flex flex-row p-1 rounded-md cursor-pointer hover:bg-blue-800 w-full"
            :class="{
              'bg-blue-500': selectedHistoryId === getReverseHistory.length - (index+1)
            }"
            :data-index="getReverseHistory.length - (index+1)"
            @mouseover="toggleHistorySelect(getReverseHistory.length - (index+1))"
          >
            <div @mouseover="toggleHistorySelect(getReverseHistory.length - (index+1))">
              {{ (getReverseHistory.length - index).toString().padStart(2, '0') }}: 
              <strong class="inline-block w-8">{{ item.type.toUpperCase() }}({{ item.rotation }})</strong> 
              : {{ item.x.toString().padStart(2, '0') }}, {{ item.y.toString().padStart(2, '0') }} 
            </div>
          </li>
        </ul>
      </div>

    </div>

  </div>

  <div class="search absolute top-[3.5rem] h-40 w-60">
    <input
      id="searchInput"
      v-model="searchText"
      placeholder="Search Pokémon..."
      class="w-full mb-2 text-white p-2 rounded-t bg-slate-800 border-white border-b-transparent"
      @keyup.enter="() => { search = false; searchText = ''; }"
      @blur="() => { search = false; searchText = ''; }"
      autofocus
    />
  </div>

  <div class="search absolute top-[3.5rem] right-[1rem] h-40">
    <div class="flex text-white p-2 rounded-t bg-slate-800 border border-white border-b-transparent">
      v1.0.3
    </div>
  </div>

  <div class="flex flex-col ml-auto opacity-30">
    <h3>Key Binds: </h3>
    <ul class="flex flex-col gap-1">
      <li>Left Click: Select</li>
      <li>Right Click: Track</li>
      <li v-if="selectionType === 'tetris'">R: Rotate</li>
      <li v-if="selectionType === 'tetris'">F: Flip</li>
      <li v-if="selectionType === 'tetris'">1-9: Select Tetrimino</li>
      <li>Ctrl + Z: Undo</li>
      <li>Ctrl + F: Search</li>
    </ul>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'Board',

  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      search: false,
      searchText: '',
      selectedHistoryId: null,
    }
  },

  created() {
    window.addEventListener('keydown', this.handleKeydown);

    if (this.seed === null) {
      this.$store.dispatch('tetris2/setSeed', Math.floor(Math.random() * 1000000));
      this.$store.dispatch('tetris2/setRNG');
      this.$store.dispatch('tetris2/regeneratePieces');
    }
  },

  destroyed() {
    window.removeEventListener('keydown', this.handleKeydown);
  },

  methods: {
    handleKeydown(e) {
      if (!e.key) {
        return;
      }
      let key = e?.key.toLowerCase();
      if (e.ctrlKey && key === 'r') {
        return;
      }
      if (key === 'f12') {
        return;
      }

      if (this.search) {
        if (key === 'Escape') {
          this.search = false;
          this.searchText = '';
        }
        if (e.ctrlKey && key === 'f') {
          document.getElementById('searchInput')?.focus();
          
        }
        return;
      }

      e.preventDefault();
      
      // detect ctrl + f for search
      if (e.ctrlKey && key === 'f') {
        this.search = true;
        setTimeout(() => {
          document.getElementById('searchInput')?.focus();
          console.log('Search activated', document.getElementById('searchInput'));
        }, 100);
        return;
      }   

      // detect ctrl + z for undo
      if (e.ctrlKey && key === 'z') {
        this.$store.dispatch('tetris2/undoLastAction');
        return;
      }

      if (this.selectionType === 'tetris' && key === 'r') {
        this.$store.dispatch('tetris2/rotateTetrimino');
        return;
      }

      if (this.selectionType === 'tetris' && key === 'f') {
        this.$store.dispatch('tetris2/flipTetrimino');
        return;
      }

      // ability to select the tetrimino by number key
      if (this.selectionType === 'tetris' && [1, 2, 3, 4, 5, 6, 7, 8, 9, 0].includes(parseInt(key))) {
        let keys = Object.keys(this.pieceGeneration).map((key) => parseInt(key));
        if (parseInt(key) === 0) {
          this.$store.dispatch('tetris2/setPieceSelection', 9);
        } else {
          this.$store.dispatch('tetris2/setPieceSelection', parseInt(key) - 1);
        }
      }
    },

    clearBoard() {
      if (!confirm('Are you sure you want to clear the board?')) {
        return;
      }

      this.$store.dispatch('tetris2/clearBoard');
    },

    selectCell(x, y) {
      let lastHistoryAction = this.getReverseHistory[0];
      if (lastHistoryAction && lastHistoryAction.x === x && lastHistoryAction.y === y) {
        console.log('Last action is the same cell, undoing last action');
        // if the last action is the same cell, we can just remove it
        this.$store.dispatch('tetris2/undoLastAction');
        return;
      }

      if (this.getHighlightedCells.length === 0) {
        return;
      }


      let cell = {
        type: this.selectionType === 'tetris' ? this.pieceGeneration[this.pieceSelection].type : '.',
        rotation: this.rotation,
        x: parseInt(x),
        y: parseInt(y),
      };
      this.$store.dispatch('tetris2/increaseStep');
      this.$store.dispatch('tetris2/addSelectedCell', cell);    
      
      this.$store.dispatch('tetris2/addHistory', cell);
      this.$store.dispatch('tetris2/regeneratePieces');
      this.$store.dispatch('tetris2/rotateTetrimino', parseInt(0));
    },

    trackCell(x, y) {
      this.$store.dispatch('tetris2/toggleTrackCell', { x: parseInt(x), y: parseInt(y) });
    },

    figureBgColor(x, y) {
      // selected cell above all others
      if (this.isCellSelected(x, y)) {
        return this.getSelectedCellColor(x, y);
      }

      // tracked cell next
      if (this.isCellTracked(x, y)) {
        return this.colors.trackColor;
      }

      // default background color
      return this.colors.background;
    },

    toggleHistorySelect(index) {
      if (this.selectedHistoryId === index) {
        this.selectedHistoryId = null;
      } else {
        this.selectedHistoryId = index;
      }
    },

    historyCheck(x, y) {
      if (this.selectedHistoryId === null) {
        return false;
      }
      
      return this.historyItemClass
        .map(cell => [cell.x, cell.y].join(','))
        .includes(
          [x, y].join(',')
        )
      ;
    }
  },

  computed: {
    ...mapGetters('tetris2', [
      'isCellSelected',
      'isCellTracked',
      'isCellHovered',
      'getSelectedCellColor',
      'sortPokedex',
      'getTetriminoCoords',
      'selectedPiece',
      'getReverseHistory',
    ]),
    ...mapFields('tetris2', [
      'step',
      'seed',
      'rng',
      'pieceDef',
      'pokedex',
      'selectedPokedexLength',
      'board',
      'perRow',
      'sort',
      'cellSize',
      'cellSpacing',
      'history',
      'hoverCell',
      'showHistory',
      'showGridCoords',

      'colors',
      'pieceGeneration',
      'pieceSelection',
      'rotation',
      'selectionType',

      'selectedCells',
      'trackedCells',
    ]),

    renderCells() {
      this.$store.dispatch('tetris2/generateBoard', this.pokedexData);

      return this.board?.map((row, y) => 
        row.map((pokemon, x) => ({
          ...pokemon,
          key: [x, y].join(','),
          class: {
            'border-[--hoverBorderColor]': this.getHighlightedCells.some(cell => cell.x === x && cell.y === y),
            'opacity-20': this.search && !pokemon?.name.toLowerCase().includes(this.searchText.toLowerCase()),
            'opacity-20': this.selectedHistoryId && !this.historyCheck(x, y),
          },
          style: {
            '--width': this.cellSize + 'px',
            '--height': this.cellSize + 'px',
            '--backgroundColor': this.figureBgColor(x, y),
            '--borderColor': 'transparent',
            '--hoverBorderColor': this.colors.hoverBorder,
          },
        }))
      );
    },

    getHighlightedCells() {
      return this.getTetriminoCoords(this.selectedPiece.type, this.rotation, this.hoverCell.x, this.hoverCell.y);
    },

    selectedCells2Html() {
      if (!this.selectedCells || this.selectedCells.length === 0) {
        return '';
      }

      return this.selectedCells.map(cell => `[${cell.x}, ${cell.y}]`).join(',');
    },

    historyItemClass() {
      let item = this.history[this.selectedHistoryId];
      if (!item) {
        return [];
      }
      console.log('history item', item);

      return this
        .getTetriminoCoords(item.type, item.rotation, item.x, item.y, true)
      ;
    },
  },
};
</script>