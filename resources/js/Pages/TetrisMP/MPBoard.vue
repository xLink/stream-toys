<template>
  <div class="flex w-full bg-slate-800 p-2 gap-2 items-center">
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
        :pieceDef="pieceDef"
        :colors="colors"
        @click="$store.dispatch('tetrismp/setPieceSelection', index)"
        class="bg-slate-700"
      />
    </div>

    <div class="flex flex-row text-3xl ml-auto">
      <RotateIcon 
        title="Rotate Tetriminos" 
        class="cursor-pointer" 
        @click="$store.dispatch('tetrismp/rotateTetrimino')" 
      />
      <SaveIcon
        :title="`Last Saved: ${lastSaved}`" 
        class="cursor-pointer text-green-500" 
        @click="$store.dispatch('tetrismp/saveBoard')" 
      />
      <DeleteIcon v-if="isOwner" title="Clear Board" class="cursor-pointer text-red-500" @click="clearBoard" /> 
    </div>
  </div>

  <div class="flex flex-row overflow-auto scrollbar-thin"
    :style="{
      '--calcHeight': 'calc((var(--cellSize) * (var(--rows) + 0.6)) + var(--cellSpacing) + (var(--extraPadding) * 3) + 2.5rem)',
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
      <div 
        class="flex flex-col gap-[--extraPadding]" 
        @contextmenu.prevent="() => {}"
        :style="{
          '--width': this.cellSize + 'px',
          '--height': this.cellSize + 'px',
          '--borderColor': 'transparent',
          '--hoverBorderColor': this.colors.hoverBorder,
          '--halfCellSize': (this.cellSize / 2) + 'px',
        }"
      >
        <div v-if="showGridCoords === true" class="flex flex-row gap-[--extraPadding]">
          <div class="flex min-w-[--halfCellSize]">&nbsp;</div>
          <div 
            v-for="i in perRowKeys"
            :key="i.toString().padStart(2, '0')"
            class="flex justify-center items-center min-w-[--width]" 
          >
            {{ i.toString().padStart(2, '0') }}
          </div>
        </div>
        <div v-for="(col, y) in renderCells"
          class="flex flex-row gap-[--extraPadding]"
          :data-row="y"
        >
          <div v-if="showGridCoords === true" class="flex justify-center items-center min-w-[--halfCellSize]">
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
              @mouseover="$store.dispatch('tetrismp/setHoverCell', { x: parseInt(x), y: parseInt(y) })"
            />
            <div v-else>&nbsp;</div>
          </div>
        </div>
      </div>
      
      <div class="flex flex-row mt-2">
        <div class="flex flex-row gap-2" :style="{
          '--width': '30px',
          '--height': '30px',
          '--borderColor': colors.selectedBorder,
        }">
          <div class="flex gap-1 items-center justify-center">
            <span 
              class="flex rounded !w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor]" 
              :style="{'--backgroundColor': colors.trackColor}"
            ></span> 
            {{ trackedCells.length }} Tracked
          </div>
          <div class="flex gap-1 items-center justify-center">
            <span 
              class="flex rounded !w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor]" 
              :style="{'--backgroundColor': colors.singleSelect}"
            ></span> 
            {{ selectedCells.length }} Caught
          </div>
          <div class="flex gap-1 items-center justify-center">
            <span 
              class="flex rounded !w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor]" 
              :style="{'--backgroundColor': colors.background}"
            ></span> 
            {{ selectedPokedexLength - selectedCells.length - trackedCells.length }} Unknown
          </div>
          <div v-if="mode === 'coop' || mode === null" class="flex gap-1 items-center justify-center">
            <span 
              class="flex rounded !w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor] items-center justify-center" 
              :style="{'--backgroundColor': colors.background}"
            >%</span> 
            {{ ((selectedCells.length / selectedPokedexLength) * 100).toFixed(0) }}% Complete
          </div>
          <div v-if="mode !== 'coop'" class="flex gap-1 items-center justify-center">
            <template v-for="(value, color) in percentByColors">
              <span  
                class="flex rounded !w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor] items-center justify-center" 
                :style="{'--backgroundColor': color}"
              ></span> 
              {{ value.toFixed(0) }}% Complete
            </template>
          </div>
        </div>
        <div class="flex ml-auto w-fit gap-2">
          <div class="flex items-center">
            ({{ hoverCell.x.toString().padStart(2, '0') }}, {{ hoverCell.y.toString().padStart(2, '0') }})
          </div>
          <div class="flex">
            <input
              id="searchInput"
              v-model="searchText"
              placeholder="Search Pokémon..."
              class="w-full mb-2 text-white p-2 rounded bg-slate-800 ring-transparent focus:ring-0 focus:ring-offset-0 "
              @click="() => { search = true; }"
              @keyup.enter="() => { searchText = ''; }"
              @blur="() => { search = false; searchText = ''; }"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';

export default {
  name: 'MPBoard',

  data() {
    return {
      search: false,
      searchText: '',
    }
  },

  created() {
    window.addEventListener('keydown', this.handleKeydown);
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
        }, 100);
        return;
      }   

      // detect ctrl + z for undo
      if (e.ctrlKey && key === 'z') {
        this.$store.dispatch('tetrismp/undoLastAction');
        return;
      }

      if (this.selectionType === 'tetris' && key === 'r') {
        this.$store.dispatch('tetrismp/rotateTetrimino');
        return;
      }

      if (this.selectionType === 'tetris' && key === 'f') {
        this.$store.dispatch('tetrismp/flipTetrimino');
        return;
      }

      // ability to select the tetrimino by number key
      if (this.selectionType === 'tetris' && [1, 2, 3, 4, 5, 6, 7, 8, 9, 0].includes(parseInt(key))) {
        let keys = Object.keys(this.pieceGeneration).map((key) => parseInt(key));
        if (parseInt(key) === 0) {
          this.$store.dispatch('tetrismp/setPieceSelection', 9);
        } else {
          this.$store.dispatch('tetrismp/setPieceSelection', parseInt(key) - 1);
        }
      }
    },

    clearBoard() {
      if (!this.isOwner || !confirm('Are you sure you want to clear the board?')) {
        return;
      }

      this.$store.dispatch('tetrismp/clearBoard');
    },

    selectCell(x, y) {
      let lastHistoryAction = this.getReverseHistory[0];
      if (lastHistoryAction && lastHistoryAction.x === x && lastHistoryAction.y === y) {
        // if the last action is the same cell, we can just remove it
        this.$store.dispatch('tetrismp/undoLastAction');
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
        username: this.currentPlayer,
      };
      this.$store.dispatch('tetrismp/increaseStep');
      this.$store.dispatch('tetrismp/addSelectedCell', cell);    
      
      this.$store.dispatch('tetrismp/addHistory', cell);
      this.$store.dispatch('tetrismp/regeneratePieces');
      this.$store.dispatch('tetrismp/rotateTetrimino', parseInt(0));
      this.$store.dispatch('tetrismp/saveBoard');
    },

    trackCell(x, y) {
      this.$store.dispatch('tetrismp/toggleTrackCell', { x: parseInt(x), y: parseInt(y) });
      this.$store.dispatch('tetrismp/saveBoard');
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
    },

    filterSelectedByUser(user) {
      return (this.selectedCells.filter(cell => cell.username === user).length / this.selectedPokedexLength) * 100;
    },
  },

  computed: {
    ...mapGetters('tetrismp', [
      'isOwner',
      'selectedPiece',
      'getTetriminoCoords',
      'isCellSelected',
      'isCellTracked',
      'isCellHovered',
      'getReverseHistory',
      'getSelectedCellColor',
    ]),
    ...mapFields('tetrismp', [
      'settings.perRow',
      'settings.sort',
      'settings.tetriminosToGenerate',
      'settings.pokedex',
      'settings.selectedPokedexLength',
      'settings.selectionType',
      'mode',
      'currentPlayer',
      'playerColor',
      'lastSaved',
      'cellSize',
      'cellSpacing',
      'showGridCoords', 
      'board',
      'hoverCell',
      'selectedCells',
      'trackedCells',
      'colors',
      'history',
      'selectedHistoryId',

      'pieceGeneration',
      'pieceSelection',
      'rotation',
      'pieceDef',

      'players',
    ]),

    renderCells() {
      return this.board?.map((row, y) => 
        row.map((pokemon, x) => ({
          ...pokemon,
          key: [x, y].join(','),
          class: {
            'border-[--hoverBorderColor]': this.getHighlightedCells.some(cell => cell.x === x && cell.y === y),
            'opacity-30': (this.search && !pokemon?.name.toLowerCase().includes(this.searchText.toLowerCase())) 
              || (this.selectedHistoryId !== null && !this.historyCheck(x, y)),
          },
          style: {
            '--backgroundColor': this.figureBgColor(x, y),
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

    perRowKeys() {
      let keys = [];

      for (let i = 0; i < this.perRow; i++) {
        keys.push(i);
      }

      return keys;
    },

    percentByColors() {
      if (Object.values(this.players).length === 0) {
        return null;
      }

      let colors = {};
      Object.values(this.players).forEach(player => {
        if (!(player.color in colors)) {
          colors[player.color] = 0;
        }

        let userSelected = this.selectedCells
          .filter(cell => cell.username.toLowerCase() === player.username.toLowerCase())
          .length;
        colors[player.color] += userSelected;
      });

      Object.keys(colors).forEach(color => {
        colors[color] = (colors[color] / this.selectedPokedexLength) * 100;
      });

      return colors;
    },

    historyItemClass() {
      if (this.selectedHistoryId === null) {
        return [];
      }

      let item = this.history[this.selectedHistoryId];
      if (!item) {
        return [];
      }

      return this.getTetriminoCoords(item.type, item.rotation, item.x, item.y, true);
    },
  }
}
</script>