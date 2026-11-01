<template>
  <div
    class="flex flex-col gap-4 bg-black/30 rounded-lg p-4 w-full max-h-96 overflow-y-auto scrollbar-thin"
  >
    <div v-if="isOwner" class="flex justify-between">
      <h2 class="text-2xl font-bold">Room Settings</h2>
      <Btn type="success" class="w-40" @click="syncToDB()">
        Save Settings
      </Btn>
    </div>

    <div v-if="isOwner" class="flex flex-col gap-2">
      <div class="flex gap-4 items-center w-full">
        <div class="flex gap-2 items-center justify-center w-1/3">
          <TextField
            v-model="name"
            name="name"
            label="Room Name"
            float-label
            placeholder="Enter room name"
          />
        </div>
        <div class="flex gap-2 items-center justify-center w-1/3">
          <TextField
            v-model="seed"
            name="seed"
            label="Seed"
            float-label
            placeholder="Enter seed"
          />
          <Btn type="warning" class="bg-red-500 text-white h-11 mt-8 p-2 rounded" @click="randomizeSeed">
            <RefreshIcon />
          </Btn>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-lg">Select Pokédex(es):</label>
        <div class="flex gap-4 flex-wrap">
          <div v-for="(label, key) in optionsObjects.pokedexes" :key="'dex'+key" class="flex items-center">
            <label :for="'dexc'+key">
              <input type="checkbox" :id="'dexc'+key" v-model="pokedex" :value="key">
              {{ label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex gap-4 items-center">
        <div class="flex w-1/3">
          <RangeField
            v-model="perRow"
            name="perRow"
            label="Pokémon Per Row"
            float-label
            :min="1"
            :max="40"
          />
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-lg">Ship Counts:</label>
        <div class="flex gap-4 flex-wrap">
          <div v-for="(def, key) in optionsObjects.shipDefs" :key="'ship'+key" class="flex items-center gap-2">
            <label class="text-sm w-24">{{ def.label }} ({{ def.size }})</label>
            <input
              type="number"
              :value="shipCounts[key] ?? 1"
              @input="updateShipCount(key, $event.target.value)"
              min="0"
              max="10"
              class="w-16 text-white text-sm p-1.5 rounded bg-slate-700 focus:outline-none focus:bg-slate-600"
            />
          </div>
        </div>
      </div>

      <div class="flex gap-2 items-center">
        <label class="text-lg">Shuffle Blanks:</label>
        <div class="flex space-x-4">
          <div v-for="(label, key) in optionsObjects.boolean" :key="'shuffleblanks'+key" class="flex items-center">
            <label :for="'shuffleblanks'+key">
              <input type="radio" :id="'shuffleblanks'+key" v-model="shuffleBlanks" :value="key === 'true'">
              {{ label }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Player settings (all players) -->
    <h2 class="text-2xl font-bold">Player Settings</h2>

    <div class="flex gap-4 items-center">
      <div class="player-color-input flex w-full">
        <RadioGroup
          v-model="playerColor"
          :options="playerColorOptions"
          :use-color-value="true"
          name="playerColor"
          label="Player Color"
          float-label
          @change="saveToLS()"
        />
        <div class="flex items-end ml-2 gap-1">
          <label class="flex flex-col items-center text-xs">
            Custom
            <input type="color" v-model="playerColor" class="cursor-pointer rounded w-10 h-10" @input="saveToLS()" />
          </label>
        </div>
      </div>
    </div>

    <div class="flex gap-2 items-center">
      <label class="text-md">Board Stats:</label>
      <div class="flex space-x-4">
        <div v-for="option in ['top', 'bottom', 'hidden']" :key="'stats'+option" class="flex items-center">
          <label :for="'stats'+option">
            <input type="radio" :id="'stats'+option" v-model="boardStatsPosition" :value="option" @change="saveToLS()">
            {{ option.charAt(0).toUpperCase() + option.slice(1) }}
          </label>
        </div>
      </div>
    </div>

    <div class="flex gap-2 items-center">
      <label class="text-md">Show Grid Co-ords:</label>
      <div class="flex space-x-4">
        <div v-for="(label, key) in optionsObjects.boolean" :key="'gridcoords'+key" class="flex items-center">
          <label :for="'gridcoordsc'+key">
            <input type="radio" :id="'gridcoordsc'+key" v-model="showGridCoords" :value="key" @change="saveToLS()">
            {{ label }}
          </label>
        </div>
      </div>
    </div>

    <div class="flex gap-4 items-center">
      <div class="flex w-1/3">
        <RangeField
          v-model="cellSize"
          name="cellSize"
          label="Cell Size"
          float-label
          :min="10"
          :max="100"
          :step="1"
          @change="saveToLS()"
        />
      </div>
      <div class="flex w-1/3">
        <RangeField
          v-model="cellSpacing"
          name="cellSpacing"
          label="Cell Spacing"
          float-label
          :min="0"
          :max="20"
          :step="1"
          @change="saveToLS()"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';

export default {
  name: 'BSOptions',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      lsKey: null,
    };
  },

  created() {
    this.lsKey = ['battleships', this.uuid, 'settings'].join('-');
  },

  methods: {
    randomizeSeed() {
      this.seed = Math.floor(Math.random() * 1000000);
    },

    syncToDB() {
      this.$store.dispatch('battleships/saveRoom').then(() => {
        this.$inertia.reload({ only: ['pokedexData'] });
      });
    },

    updateShipCount(key, value) {
      const count = Math.max(0, Math.min(10, parseInt(value) || 0));
      const updated = { ...this.shipCounts, [key]: count };
      this.$store.commit('battleships/updateField', { path: 'shipCounts', value: updated });
    },

    saveToLS() {
      const settings = {
        showGridCoords: this.showGridCoords,
        cellSize: this.cellSize,
        cellSpacing: this.cellSpacing,
        playerColor: this.playerColor,
        boardStatsPosition: this.boardStatsPosition,
      };
      localStorage.setItem(this.lsKey, JSON.stringify(settings));
    },
  },

  computed: {
    ...mapGetters('battleships', [
      'isOwner',
    ]),
    ...mapFields('battleships', [
      'uuid',
      'name',
      'seed',
      'settings.perRow',
      'settings.pokedex',
      'cellSize',
      'cellSpacing',
      'showGridCoords',
      'boardStatsPosition',
      'shuffleBlanks',
      'blankRows',
      'shipCounts',
      'playerColor',
      'players',
      'currentPlayer',
    ]),

    playerColorOptions() {
      const colors = { ...this.optionsObjects.colors };
      if (this.players) {
        Object.values(this.players).forEach(p => {
          if (p.color && p.username !== this.currentPlayer && !colors[p.color]) {
            colors[p.color] = p.username;
          }
        });
      }
      return colors;
    },
  },

  watch: {
    playerColor() {
      if (this.currentPlayer) {
        this.$store.dispatch('battleships/updatePlayer', {
          username: this.currentPlayer,
          color: this.playerColor,
        });
      }
    },

    seed() {
      if (this.isOwner) {
        this.syncToDB();
      }
    },
  },
};
</script>
