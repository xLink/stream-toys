<template>
  <div 
    class="flex flex-col gap-4 bg-black/30 rounded-lg p-4 w-full"
  >
    <div v-if="isOwner" class="flex justify-between">
      <div class="flex text-lg mb-4">
        <h2 class="text-2xl font-bold text-center">
          Room Settings
        </h2>
      </div>
      <div class="flex gap-2">
        <Btn type="success" class="w-40" @click="syncToDB()">
          Sync Settings
        </Btn>
      </div>
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
            v-model="password"
            name="password"
            label="Password (optional)"
            float-label
          />
        </div>
        <div class="flex gap-2 items-center justify-center w-1/3">
          <TextField
            v-model="seed"
            name="seed"
            label="Seed"
            float-label
            placeholder="Enter seed (optional)"
          />
          <Btn type="warning" class="bg-red-500 text-white h-11 mt-8 p-2 rounded" @click="randomizeSeed">
            <RefreshIcon  />
          </Btn>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-lg">Game Mode:</label>
        <div class="flex flex-col gap-4 flex-wrap">
          <div v-for="(value, key) in optionsObjects.mode" :key="'mode'+key" class="flex items-center">
            <label :for="'modec'+key">
              <input type="radio" :id="'modec'+key" v-model="mode" :value="key">
              {{ value.label }}
              <span class="text-sm text-gray-500">{{ value.info }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-lg">Select Pokedex(es):</label>
        <div class="flex gap-4 flex-wrap">
          <div v-for="(label, key) in optionsObjects.pokedexes" :key="'dex'+key" class="flex items-center">
            <label :for="'dexc'+key">
              <input type="checkbox" :id="'dexc'+key" v-model="pokedex" :value="key">
              {{ label }}
            </label>
          </div>
        </div>
      </div>

      <div v-if="false" class="flex gap-2 items-center">
        <label class="text-lg">Sort:</label>
        <div class="flex space-x-4">
          <div v-for="(label, key) in optionsObjects.sort" :key="'sort'+key" class="flex items-center">
            <label :for="'sortr'+key">
              <input type="radio" :id="'sortr'+key" v-model="sort" :value="key">
              {{ label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex gap-2 items-center">
        <div class="flex gap-2 items-center">
          <label class="text-lg">Selection:</label>
          <div class="flex space-x-4">
            <div v-for="(label, key) in selectionOptions" :key="'select'+key" class="flex items-center">
              <label :for="'selectr'+key">
                <input type="radio" :id="'selectr'+key" v-model="selectionType" :value="key">
                {{ label }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <div v-if="false" class="flex gap-2 items-center">
        <div class="flex gap-2 items-center">
          <label class="text-lg">Color Grid Selections:</label>
          <div class="flex space-x-4">
            <div v-for="(label, key) in useTetrisColorsOptions" :key="'grid'+key" class="flex items-center">
              <label :for="'gridc'+key">
                <input type="radio" :id="'gridc'+key" :name="'gridc'+key" v-model="useTetrisColors" :value="key">
                {{ label }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-4 items-center" v-if="selectionType === 'tetris'">
        <div class="flex space-x-4 w-1/3">
          <RangeField
            v-model="tetriminosToGenerate"
            name="tetriminosToGenerate"
            id="tetriminosToGenerate"
            label="Tetriminos to Generate"
            float-label
            :min="1"
            :max="10"
          />
        </div>
        <div class="flex w-1/3"></div>
        <div class="flex w-1/3"></div>
      </div>

      <div class="flex gap-4 items-center">
        <div class="flex w-1/3">
          <RangeField
            v-model="perRow"
            name="perRow"
            id="perRow"
            label="Pokémon Per Row"
            float-label
            :min="1"
            :max="40"
          />
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <div class="flex justify-between">
        <div class="flex text-lg mb-4">
          <h2 class="text-2xl font-bold text-center">
            {{ currentPlayer }} Settings
          </h2>
        </div>
      </div>

      <div class="flex gap-4 items-center">
        <div class="player-color-input flex w-full">
          <RadioGroup
            v-model="playerColor"
            :options="optionsObjects.colors"
            :use-color-value="true"
            name="playerColor"
            label="Player Color"
            float-label
            @change="saveToLS()"
          />
        </div>
      </div>

      <div class="flex gap-2 items-center">
        <div class="flex gap-2 items-center">
          <label class="text-md">Show Grid Co-ords:</label>
          <div class="flex space-x-4">
            <div v-for="(label, key) in optionsObjects.boolean" :key="'gridcoords'+key" class="flex items-center">
              <label :for="'gridcoordsc'+key">
                <input type="radio" :id="'gridcoordsc'+key" :name="'gridcoordsc'+key" v-model="showGridCoords" :value="key" @change="saveToLS()">
                {{ label }}
              </label>
            </div>
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
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';

export default {
  name: 'MPOptions',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      lsKey: null,
      selectionOptions: {
        'single': 'Single',
        'tetris': 'Tetris',
      },
    };
  },

  created() {
    this.lsKey = ['tetrismp', this.uuid, 'settings'].join('-');
  },

  methods: {
    randomizeSeed() {
      this.seed = Math.floor(Math.random() * 1000000);
    },

    syncToDB() {
      this.$store.dispatch('tetrismp/saveBoard');
    },

    saveToLS() {
      const settingsToSave = {
        showGridCoords: this.showGridCoords,
        cellSize: this.cellSize,
        cellSpacing: this.cellSpacing,
        playerColor: this.playerColor,
      };
      localStorage.setItem(this.lsKey, JSON.stringify(settingsToSave));
    },
  },

  computed: {
    ...mapGetters('tetrismp', [
      'isOwner',
    ]),
    ...mapFields('tetrismp', [
      'name',
      'uuid',
      'seed',
      'mode',
      'password',
      'settings.perRow',
      'settings.sort',
      'settings.tetriminosToGenerate',
      'settings.pokedex',
      'settings.selectedPokedexLength',
      'settings.selectionType',
      'cellSize',
      'cellSpacing',
      'colors',
      'useTetrisColors',
      'showHistory',
      'showGridCoords',
      'currentPlayer',
      'playerColor',
    ]),
  },

  watch: {
    playerColor() {
      if (this.currentPlayer) {
        this.$store.dispatch('tetrismp/updatePlayer', {
          username: this.currentPlayer,
          color: this.playerColor,
        });
      }
    },
  },
};
</script>