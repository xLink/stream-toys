<template>
  <div class="flex flex-col flex-1 min-h-0 h-full">
    <!-- Ship selector toolbar (setup phase) -->
    <div v-if="phase === 'setup'" class="flex w-full bg-slate-800 p-2 gap-2 items-center shrink-0">
      <div class="flex flex-row gap-2">
        <div
          v-for="(def, key) in expandedShipDefs"
          :key="key"
          class="flex items-center gap-1 px-3 py-2 rounded cursor-pointer select-none transition-all"
          :class="{
            'bg-blue-600 text-white': placingShip === key,
            'bg-slate-700 text-white hover:bg-slate-600': placingShip !== key && !isShipPlaced(key),
            'bg-green-800 text-green-300 hover:bg-green-700': isShipPlaced(key) && placingShip !== key,
          }"
          @click="selectShip(key)"
        >
          <span class="flex gap-0.5">
            <span v-for="i in def.size" :key="i" class="w-3 h-3 rounded-sm" :class="isShipPlaced(key) ? 'bg-green-500' : 'bg-white'"></span>
          </span>
          <span class="ml-1 text-sm">{{ def.label }}</span>
          <span v-if="isShipPlaced(key)" class="text-green-400 ml-1">✓</span>
        </div>
      </div>
      <div class="flex ml-auto text-sm text-slate-400">
        Press <kbd class="mx-1 px-1 bg-slate-600 rounded">R</kbd> to rotate
      </div>
    </div>

    <!-- Battle phase toolbar -->
    <div v-if="phase === 'battle'" class="flex w-full bg-slate-800 p-2 gap-2 items-center shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-4 h-4 rounded" :style="{ backgroundColor: turnColor }"></div>
        <span v-if="isMyTurn" class="text-yellow-400 font-bold text-lg animate-pulse">YOUR TURN!</span>
        <span v-else class="text-slate-300">Waiting for {{ turnPlayerName }}...</span>
      </div>
      <div class="flex ml-auto items-center gap-3">
        <button
          v-if="!inputDisabled"
          class="flex items-center gap-1 px-3 py-1 rounded text-sm transition-colors"
          :class="showShips ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
          @click="showShips = !showShips"
        >
          {{ showShips ? 'Hide Ships' : 'Show Ships' }}
        </button>
        <input
          id="bsSearchInput"
          v-model="searchText"
          placeholder="Search Pokémon..."
          class="w-48 text-white text-sm p-1.5 rounded bg-slate-700 ring-transparent focus:ring-0 focus:ring-offset-0 focus:outline-none focus:bg-slate-600"
          @click="search = true"
          @keyup.enter="searchText = ''"
          @blur="search = false; searchText = ''"
        />
      </div>
    </div>

    <!-- Setup phase search -->
    <div v-if="phase === 'setup'" class="flex w-full bg-slate-800 px-2 pb-2 gap-2 items-center shrink-0 justify-end">
      <button
        v-if="!inputDisabled"
        class="flex items-center gap-1 px-3 py-1 rounded text-sm transition-colors"
        :class="showShips ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
        @click="showShips = !showShips"
      >
        {{ showShips ? 'Hide Ships' : 'Show Ships' }}
      </button>
      <input
        v-model="searchText"
        placeholder="Search Pokémon..."
        class="w-48 text-white text-sm p-1.5 rounded bg-slate-700 ring-transparent focus:ring-0 focus:ring-offset-0 focus:outline-none focus:bg-slate-600"
        @click="search = true"
        @keyup.enter="searchText = ''"
        @blur="search = false; searchText = ''"
      />
    </div>

    <!-- Finished toolbar -->
    <div v-if="phase === 'finished'" class="flex w-full bg-green-800 p-2 gap-2 items-center shrink-0 justify-center">
      <span class="text-white font-bold text-lg">Game Over!</span>
    </div>

    <div class="flex flex-row flex-1 overflow-auto scrollbar-thin min-h-0">
      <div
        id="pokeboard"
        class="flex flex-col w-full p-2 overflow-auto scrollbar-thin"
        :style="{ '--extraPadding': (cellSpacing / 4) + 'rem' }"
      >
        <!-- Stats -->
        <div v-if="boardStatsPosition === 'top'" class="flex flex-row mb-2 gap-4">
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block" :style="{ backgroundColor: playerColor }"></span>
            Your Ships
          </div>
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block border-2 border-red-500" style="background:#991b1b"></span>
            Hit
          </div>
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block" style="background:#374151"></span>
            Miss
          </div>
          <div class="flex ml-auto items-center text-sm text-slate-400">
            ({{ hoverCell.x.toString().padStart(2, '0') }}, {{ hoverCell.y.toString().padStart(2, '0') }})
          </div>
        </div>

        <!-- Board grid -->
        <div
          class="flex flex-col gap-[--extraPadding] w-fit min-w-full"
          @contextmenu.prevent="() => {}"
          :style="{
            '--width': cellSize + 'px',
            '--height': cellSize + 'px',
            '--borderColor': 'transparent',
            '--hoverBorderColor': colors.hoverBorder,
            '--halfCellSize': (cellSize / 2) + 'px',
          }"
        >
          <!-- Column headers -->
          <div v-if="showGridCoords === true || showGridCoords === 'true'" class="flex flex-row gap-[--extraPadding]">
            <div class="flex min-w-[--halfCellSize]">&nbsp;</div>
            <div
              v-for="i in perRowKeys"
              :key="i"
              class="flex justify-center items-center min-w-[--width]"
            >
              {{ i.toString().padStart(2, '0') }}
            </div>
          </div>

          <!-- Rows -->
          <div v-for="(col, y) in renderCells"
            class="flex flex-row gap-[--extraPadding]"
            :data-row="y"
          >
            <div v-if="showGridCoords === true || showGridCoords === 'true'" class="flex justify-center items-center min-w-[--halfCellSize]">
              {{ y.toString().padStart(2, '0') }}
            </div>

            <div v-for="(pokemon, x) in col"
              class="flex rounded min-w-[--width] h-[--height] border-2 transition-all duration-200 ease-in-out"
              :key="pokemon.key"
              :class="[pokemon.class, {
                'cursor-pointer hover:brightness-125 hover:border-yellow-400': phase === 'battle' && isMyTurn && !isCellShotByMe(x, y),
                'cursor-not-allowed': phase === 'battle' && (!isMyTurn || isCellShotByMe(x, y)),
              }]"
              :style="pokemon.style"
              :data-x="x"
              :data-y="y"
            >
              <img
                v-if="typeof pokemon.image !== 'undefined'"
                :src="pokemon?.image"
                :alt="pokemon?.name"
                class="w-full h-full object-contain"
                @click="handleClick(x, y)"
                @contextmenu.prevent="handleRightClick(x, y)"
                @mouseover="$store.dispatch('battleships/setHoverCell', { x: parseInt(x), y: parseInt(y) })"
              />
              <div v-else class="w-full h-full"
                @click="handleClick(x, y)"
                @contextmenu.prevent="handleRightClick(x, y)"
                @mouseover="$store.dispatch('battleships/setHoverCell', { x: parseInt(x), y: parseInt(y) })"
              >&nbsp;</div>
            </div>
          </div>
        </div>

        <!-- Bottom stats -->
        <div v-if="boardStatsPosition === 'bottom'" class="flex flex-row mt-2 gap-4">
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block" :style="{ backgroundColor: playerColor }"></span>
            Your Ships
          </div>
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block border-2 border-red-500" style="background:#991b1b"></span>
            Hit
          </div>
          <div class="flex gap-2 items-center text-sm">
            <span class="w-4 h-4 rounded inline-block" style="background:#374151"></span>
            Miss
          </div>
          <div class="flex ml-auto items-center text-sm text-slate-400">
            ({{ hoverCell.x.toString().padStart(2, '0') }}, {{ hoverCell.y.toString().padStart(2, '0') }})
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
  name: 'BSBoard',

  props: {
    inputDisabled: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      cooldown: false,
      search: false,
      searchText: '',
      showShips: true,
    };
  },

  created() {
    window.addEventListener('keydown', this.handleKeydown);
  },

  unmounted() {
    window.removeEventListener('keydown', this.handleKeydown);
  },

  methods: {
    handleKeydown(e) {
      if (!e.key || this.inputDisabled) return;
      let key = e?.key.toLowerCase();
      if (e.ctrlKey && key === 'r') return;
      if (key === 'f12') return;

      if (this.search) {
        if (key === 'escape') {
          document.getElementById('bsSearchInput')?.blur();
        }
        return;
      }

      if (e.ctrlKey && key === 'f') {
        e.preventDefault();
        this.search = true;
        setTimeout(() => {
          document.getElementById('bsSearchInput')?.focus();
        }, 100);
        return;
      }

      if (this.phase === 'setup' && key === 'r') {
        e.preventDefault();
        const newOrientation = this.placingOrientation === 0 ? 1 : 0;
        this.$store.commit('battleships/updateField', { path: 'placingOrientation', value: newOrientation });
      }
    },

    handleClick(x, y) {
      x = parseInt(x);
      y = parseInt(y);

      if (this.phase === 'setup' && this.placingShip) {
        this.$store.dispatch('battleships/placeShip', {
          shipType: this.placingShip,
          orientation: this.placingOrientation,
          x, y,
        });
        return;
      }

      if (this.phase === 'battle' && this.isMyTurn) {
        if (this.cooldown) return;
        this.cooldown = true;
        setTimeout(() => { this.cooldown = false; }, 500);
        this.$store.dispatch('battleships/fireShot', { x, y });
      }
    },

    handleRightClick(x, y) {
      x = parseInt(x);
      y = parseInt(y);

      if (this.phase === 'setup') {
        const ship = this.myShips.find(s =>
          s.cells.some(c => c.x === x && c.y === y)
        );
        if (ship) {
          this.$store.dispatch('battleships/removeShip', ship.shipType);
        }
      }
    },

    selectShip(key) {
      if (this.placingShip === key) {
        this.$store.commit('battleships/updateField', { path: 'placingShip', value: null });
        return;
      }
      if (this.isShipPlaced(key)) {
        this.$store.dispatch('battleships/removeShip', key);
      }
      this.$store.commit('battleships/updateField', { path: 'placingShip', value: key });
    },

    isShipPlaced(key) {
      return this.myShips.some(s => s.shipType === key);
    },

    hexToRgba(hex, alpha) {
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    getCellShots(x, y) {
      return this.shots.filter(s => s.x === x && s.y === y);
    },

    figureBgColor(x, y) {
      if (this.showShips && this.isCellMyShip(x, y)) {
        return this.playerColor;
      }

      const cellShots = this.getCellShots(x, y);
      if (cellShots.length === 0) return this.colors.background;

      if (cellShots.length >= 2) {
        const colors = cellShots.map(s => {
          const alpha = s.hit ? 0.5 : 0.2;
          return this.hexToRgba(s.shooterColor, alpha);
        });
        return `linear-gradient(135deg, ${colors[0]} 50%, ${colors[1]} 50%)`;
      }

      const shot = cellShots[0];
      return shot.hit
        ? this.hexToRgba(shot.shooterColor, 0.5)
        : this.hexToRgba(shot.shooterColor, 0.2);
    },

    figureBorderColor(x, y) {
      if (this.getHighlightedCells.some(c => c.x === x && c.y === y)) {
        return this.colors.hoverBorder;
      }

      if (this.showShips && this.isCellMyShip(x, y)) {
        const shot = this.getCellShot(x, y);
        if (shot && shot.hit) return '#ef4444';
      }

      const shot = this.getCellShot(x, y);
      if (shot && shot.hit) return '#ef4444';

      return 'transparent';
    },

    isCellMyShip(x, y) {
      return this.myShips.some(s =>
        s.cells.some(c => c.x === x && c.y === y)
      );
    },

    getCellShot(x, y) {
      return this.shots.find(s => s.x === x && s.y === y) || null;
    },

    isCellShotByMe(x, y) {
      return this.shots.find(s => s.x === x && s.y === y && s.shooterColor === this.playerColor) || null;
    },
  },

  watch: {
    phase(val) {
      if (val === 'battle') {
        this.showShips = false;
      }
    },
  },

  computed: {
    ...mapGetters('battleships', [
      'isMyTurn',
      'allShipsPlaced',
      'getShipCells',
      'isValidShipPlacement',
      'expandedShipDefs',
    ]),
    ...mapFields('battleships', [
      'phase',
      'turnColor',
      'board',
      'myShips',
      'shots',
      'shipDefs',
      'placingShip',
      'placingOrientation',
      'hoverCell',
      'cellSize',
      'cellSpacing',
      'showGridCoords',
      'boardStatsPosition',
      'colors',
      'playerColor',
      'settings.perRow',
      'players',
      'sunkCells',
      'explodingCells',
    ]),

    turnPlayerName() {
      if (!this.players || !this.turnColor) return 'opponent';
      const player = Object.values(this.players).find(p => p.color === this.turnColor);
      return player?.username || 'opponent';
    },

    perRowKeys() {
      let keys = [];
      for (let i = 0; i < this.perRow; i++) { keys.push(i); }
      return keys;
    },

    getHighlightedCells() {
      if (this.phase === 'setup' && this.placingShip && this.hoverCell.x >= 0 && this.hoverCell.y >= 0) {
        const size = this.shipDefs[this.placingShip]?.size;
        if (!size) return [];
        const cells = this.getShipCells(size, this.placingOrientation, this.hoverCell.x, this.hoverCell.y);
        if (this.isValidShipPlacement(this.placingShip, this.placingOrientation, this.hoverCell.x, this.hoverCell.y)) {
          return cells;
        }
        return [];
      }
      return [];
    },

    renderCells() {
      return this.board?.map((row, y) =>
        row.map((pokemon, x) => ({
          ...pokemon,
          key: [x, y].join(','),
          class: {
            'border-[--hoverBorderColor]': this.getHighlightedCells.some(c => c.x === x && c.y === y),
            'border-red-500': this.figureBorderColor(x, y) === '#ef4444',
            'border-transparent': this.figureBorderColor(x, y) === 'transparent' && !this.getHighlightedCells.some(c => c.x === x && c.y === y),
            'opacity-30': this.search && this.searchText && !(pokemon?.name ?? '').toLowerCase().includes(this.searchText.toLowerCase()),
            'bs-explode': this.explodingCells.includes(`${x},${y}`),
            'opacity-40 grayscale': this.sunkCells.includes(`${x},${y}`),
          },
          style: {
            background: this.figureBgColor(x, y),
          },
        }))
      );
    },
  },
};
</script>

<style scoped>
@keyframes bs-explosion {
  0% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.8), inset 0 0 0 0 rgba(255, 200, 50, 0.6);
    transform: scale(1);
  }
  20% {
    box-shadow: 0 0 20px 8px rgba(255, 160, 0, 0.9), inset 0 0 15px 5px rgba(255, 220, 50, 0.8);
    transform: scale(1.15);
  }
  50% {
    box-shadow: 0 0 30px 12px rgba(239, 68, 68, 0.7), inset 0 0 20px 8px rgba(255, 100, 0, 0.5);
    transform: scale(1.05);
  }
  100% {
    box-shadow: 0 0 0 0 transparent, inset 0 0 0 0 transparent;
    transform: scale(1);
  }
}

.bs-explode {
  animation: bs-explosion 0.8s ease-out;
  z-index: 10;
  position: relative;
}
</style>
