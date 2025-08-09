<template> 
  <div class="flex flex-col gap-2">
    <div class="flex flex-col bg-slate-800 p-2" v-if="selectionType === 'tetris' && tetriminos.length > 0">
      <p>Generated: {{ step ?? 0 }}</p>
      <div class="flex flex-row w-full justify-between">
        <div class="flex flex-row">
          <Tetrimino 
            v-for="(tetrimino, i) in tetriminos"
            :key="'tetri'+i"
            :type="tetrimino.type" 
            :rotation="this.rotation === null ? tetrimino.rotation : this.rotation"
            class="border-2 border-transparent cursor-pointer w-fit p-2"
            :class="{
              'border-white': selectedTetrimino === i,
            }"
            @click="selectedTetrimino = i"
          />
        </div>
        <div class="flex flex-row items-center justify-end text-3xl">
          <RefreshIcon title="Refresh Tetriminos" class="cursor-pointer" @click="rotateTetriminos" />
          <SaveIcon title="Save Tetriminos" class="cursor-pointer text-green-500" @click="saveBoard" />
          <DeleteIcon title="Clear Board" class="cursor-pointer text-red-500" @click="clearBoard" /> 
        </div>
      </div>
    </div>

    <div id="pokeboard" class="flex flex-col">
      <div 
        v-for="(row, y) in selectedPokedex" 
        class="flex flex-row "
      >
        <div
          v-for="(pokemon, x) in row"
          :key="[x, y].join(',')"
          class="pokemon-cell w-[--width] h-[--height] border border-[--borderColor] transition-all duration-200 ease-in-out "
          :class="{ 
            'border-[--hoverBorderColor]': isCellHovered(x, y) && !isCellSelected(x, y) && tetrisSelection.length > 0,
            [isCellSelected(x, y) ? getSelectedCellColor(x, y) : 'bg-[--backgroundColor]']: isCellSelected(x, y),
            'bg-[--backgroundColor]': !isCellSelected(x, y),
          }"
          :style="{
            '--width': cellSize + 'px',
            '--height': cellSize + 'px',
            '--backgroundColor': isCellSelected(x, y) && getSelectedCellColor(x, y) != null ? getSelectedCellColor(x, y) : colors.background,
            '--borderColor': 'transparent',//colors.border,
            '--hoverBorderColor': colors.hoverBorder,
          }"
          :data-pokemon-id="(pokemon?.id || '')"
          :data-x="x"
          :data-y="y"
          @click="selectCell(x, y)"
          @mouseover="() => hoverCell(x, y)"
        >
          <img 
            v-if="pokemon !== null"
            :src="pokemon?.image" 
            :alt="pokemon?.name"
            class="w-full h-full object-contain"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { prng_alea as RNG } from 'esm-seedrandom';

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
      highlightedCells: [],
      
      tetriminosGenerated: 0,
      selectedTetrimino: 0,
      selectedCell: { x: null, y: null },
      hoveredCell: { x: null, y: null },
      rotation: 0,
      tetriminos: [
        { type: '.', rotation: 0 },
        { type: 'j', rotation: 0 },
        { type: 'l', rotation: 0 },
        { type: 'z', rotation: 0 },
        { type: 's', rotation: 0 },
        { type: 'o', rotation: 0 },
        { type: 't', rotation: 0 },
        { type: 'i', rotation: 0 },
      ],
    };
  },

  created() {
    window.addEventListener('keydown', this.handleKeydown);

    this.$eventBus.$on('tetris/loadSlot', () => {
      console.log('[tetris/loadSlot] Loading slot');
      let step = this.step || 0;
      console.log('[tetris/loadSlot] Step:', step);
      this.step = 0;
      if (step > 0) {
        for (let i = 0; i < step; i++) {
          console.log('[tetris/loadSlot] Generating tetriminos for step', i);
          this.generateTetriminos();
        }
      } else {
        this.generateTetriminos();
      }
    });
  },

  methods: {
    sortPokedex(pokedex) {
      if (!pokedex || pokedex.length === 0) {
        return [];
      }

      let sorted = [...pokedex];

      switch (this.sort) {
        case 'byId':
          sorted.sort((a, b) => a.id - b.id);
          break;

        case 'byName':
          sorted.sort((a, b) => a.name.localeCompare(b.name));
          break;

        case 'random':
        default:
          sorted = this.shuffle(sorted);
          break;
      }

      return sorted;
    },

    shuffle(array) {
      let rng = RNG(this.seed);
      for (let i = array.length - 1; i > 0; i--) {
          const j = Math.floor(rng() * (i + 1));
          [array[i], array[j]] = [array[j], array[i]];
      }
      return array;
    },

    pickRandomTetrimino() {
      const types = Object.keys(this.pieceDef).filter(type => type !== '.');
      const randomType = types[Math.floor(this.rngenerator() * types.length)];
      const randomRotation = Math.floor(this.rngenerator() * 4);
      return { type: randomType, rotation: randomRotation };
    },

    generateTetriminos() {
      this.tetriminos = [];
      for (let i = 0; i < this.tetriminosToGenerate; i++) {
        this.tetriminos[i] = this.pickRandomTetrimino();
      }
      this.tetriminos[this.tetriminos.length] = {
        type: '.',
        rotation: 0,
      }
      this.step++;
    },

    isCellSelected(x, y) {
      return this.selectedCells?.some(cell => cell.x === x && cell.y === y);
    },

    getSelectedCellColor(x, y) {
      if (this.colorGrid === 'no') {
        return '#6b7280';
      }
      const cell = this.selectedCells?.find(cell => cell.x === x && cell.y === y);
      return cell?.color ?? '#6b7280';
    },

    isCellHovered(x, y) {
      // highlight cells based on currently selected tetrimino
      if (this.selectionType !== 'tetris' || this.selectedTetrimino === null) {
        return false;
      }
      const tetrimino = this.tetriminos[this.selectedTetrimino];
      if (!tetrimino) {
        return false;
      }

      return this.tetrisSelection?.some(cell => cell.x === x && cell.y === y);
    },

    selectCell(x, y) {
      if (this.selectionType === 'single') {
        this.selectedCells.push({ x: x, y: y });
      } else if (this.selectionType === 'tetris') {
        if (this.tetrisSelection.length === 0) {
          return;
        }

        this.tetrisSelection.forEach(cell => {
          this.$store.dispatch('tetris/addSelectedCell', {
            x: cell.x,
            y: cell.y,
            color: this.pieceDef[this.tetriminos[this.selectedTetrimino].type].color,
          });
        });

        this.generateTetriminos();
      }
    },

    hoverCell(x, y) {
      this.hoveredCell = { x: x, y: y };
    },

    rotateTetriminos() {
      this.rotation++;
      if (this.rotation >= 4) {
        this.rotation = 0;
      }
    },
    
    handleKeydown(e) {
      if (!e.key) {
        return;
      }

      if (e.key === 'r') {
        this.rotateTetriminos();
      }

      // ability to select the tetrimino by number key
      if ([1, 2, 3, 4, 5, 6, 7, 8, 9].includes(parseInt(e.key))) {
        if (parseInt(e.key) - 1 in this.tetriminos) {
          this.selectedTetrimino = parseInt(e.key) - 1;
        }
      }

      if (parseInt(e.key) === 0) {
        this.selectedTetrimino = 9;
      }
    },

    clearBoard() {
      if (!confirm('This will clear the board, are you sure?')) {
        return;
      }
      this.selectedCells = [];
      this.hoveredCell = { x: null, y: null };
      this.tetriminos = [];
      this.generateTetriminos();
    },

    saveBoard() {
      this.$eventBus.$emit('tetris/saveBoard');
    }
  },

  computed: {
    ...mapFields('tetris', [
      'step',
      'seed',
      'pokedex',
      'perRow',
      'sort',
      'cellSize',
      'colors',
      'selectionType',
      'tetriminosToGenerate', 
      'pieceDef',
      'selectedCells',
      'colorGrid',
    ]),

    rngenerator() {
      return RNG(this.seed);
    },

    selectedPokedex() {
      if (!this.pokedexData || !this.pokedex || this.pokedex.length === 0) {
        return [];
      }

      let dexByGen = {
        gen1: [0, 151],
        gen2: [152, 251],
        gen3: [252, 386],
        gen4: [387, 493],
        gen5: [494, 649],
        gen6: [650, 721],
        gen7: [722, 809],
        gen8: [810, 905],
        gen9: [906, 1025],
      };

      // Filter dexByGen based on this.pokedex
      let selectedGens = this.pokedex.map(gen => {
          if (!dexByGen[gen]) {
            console.warn(`Unknown generation: ${gen}`);
            return [];
          }
          
          return Object.values(this.pokedexData).slice(dexByGen[gen][0], dexByGen[gen][1]);
        })
        .flat()
      ;

      // Sort the selected pokedex based on the selected sort option
      selectedGens = this.sortPokedex(selectedGens);
      selectedGens = selectedGens
        .reduce((acc, curr, index) => {
          if (index % this.perRow === 0) {
            acc.push([]);
          }
          acc[acc.length - 1].push(curr);

          return acc;
        }, [])
      ;

      // last row padding to ensure it meets perRow
      if (selectedGens[selectedGens.length - 1].length < this.perRow) {
        let lastRow = selectedGens[selectedGens.length - 1];
        while (lastRow.length < this.perRow) {
          lastRow.push(null);
        }
      }

      return selectedGens;
    },

    selectedCells() {

    },

    tetrisSelection() {
      let activePiece = this.tetriminos[this.selectedTetrimino]?.type;
      let rotation = this.rotation || 0;
      let x = parseInt(this.hoveredCell.x);
      let y = parseInt(this.hoveredCell.y);

      if (x === null || y === null || !activePiece) {
        return [];
      }

      if (!this.pieceDef[activePiece]) {
        return [];
      }

      // calc start position based on piece type and rotation
      let startPos = this.pieceDef[activePiece].start[rotation] || [0, 0];
      x -= startPos[0];
      y -= startPos[1];

      let piece = [
        [0, 0, 0, 0],
        [0, 0, 0, 0],
        [0, 0, 0, 0],
        [0, 0, 0, 0]
      ];

      let row = 0;
      let col = 0;
      let hoverCells = [];

      for(let bit = 0x8000 ; bit > 0 ; bit = bit >> 1) {
        if (this.pieceDef[activePiece].blocks[rotation] & bit) {
          piece[col][row] = 1;
          hoverCells.push({ x: x + col, y: y + row });
        }
        if (++col === 4) {
          col = 0;
          ++row;
        }
      }

      // if any of the hoverCells are out of bounds, return empty array
      if (this.hoveredCell.x < 0 || this.hoveredCell.x >= this.perRow 
        || this.hoveredCell.y < 0 || this.hoveredCell.y >= (this.selectedPokedex.length * this.perRow) / this.perRow) {
        return [];
      }

      // if any of the hoverCells are already selected, return empty array
      if (hoverCells.some(cell => this.isCellSelected(cell.x, cell.y))) {
        return [];
      }

      // check if any of the hoverCells dont have a pokemon in the selectedPokedex
      if (hoverCells.some(cell => !this.selectedPokedex[cell.y] 
        || !this.selectedPokedex[cell.y][cell.x]
      )) {
        return [];
      }

      return hoverCells;
    },
  },

  watch: {
    'seed': function () {
      this.generateTetriminos();
    },
    'tetriminosToGenerate': function () {
      this.generateTetriminos();
    },
  },
};
</script>