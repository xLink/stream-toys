<template>
  <div 
    class="flex flex-col gap-4 bg-black/30 rounded-lg p-4 w-full"
  >
    <div class="flex justify-between">
      <div class="flex text-lg mb-4">
        Active Slot: {{ this.slots[this.activeSlot]?.name || 'None' }}
      </div>
      <div class="flex gap-2">
        <Btn type="info" class="w-40" @click="showSettings = !showSettings">
          {{ showSettings ? 'Show Settings' : 'Show Save Slots' }}
        </Btn>
      </div>
    </div>

    <div class="flex flex-col gap-2" v-if="!showSettings">
      <div class="flex gap-4 items-center w-full">
        <div class="flex gap-2 items-center justify-center w-1/3">
          <TextField
            v-model="seed"
            name="seed"
            label="Seed"
            float-label
            placeholder="Enter seed (optional)"
          />
          <Btn type="warning" class="bg-red-500 text-white h-11 mt-8 p-2 rounded" @click="randomizeSeed">
            <RefreshIcon />
          </Btn>
        </div>
      </div>

      <div class="flex gap-2 items-center">
        <label class="text-lg">Select Pokedex:</label>
        <div class="flex space-x-4">
          <div v-for="(label, key) in dexOptions" :key="'dex'+key" class="flex items-center">
            <label :for="'dexc'+key">
              <input type="checkbox" :id="'dexc'+key" v-model="pokedex" :value="key">
              {{ label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex gap-2 items-center">
        <label class="text-lg">Sort:</label>
        <div class="flex space-x-4">
          <div v-for="(label, key) in sortOptions" :key="'sort'+key" class="flex items-center">
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

      <div class="flex gap-4 items-center" v-if="selectionType === 'tetris'">
        <div class="flex space-x-4 w-1/3">
          <RangeField2
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
          <RangeField2
            v-model="perRow"
            name="perRow"
            id="perRow"
            label="Per Row"
            float-label
            :min="1"
            :max="40"
          />
        </div>
        <div class="flex w-1/3">
          <RangeField2
            v-model="cellSize"
            name="cellSize"
            label="Cell Size"
            float-label
            :min="10"
            :max="100"
            :step="1"
          />
        </div>
        <div class="flex w-1/3 flex-col">
          <label class="text-lg">Color Grid Selections:</label>
          <div class="flex space-x-4">
            <div v-for="(label, key) in gridColorOptions" :key="'grid'+key" class="flex items-center">
              <label :for="'gridc'+key">
                <input type="radio" :id="'gridc'+key" :name="'gridc'+key" v-model="colorGrid" :value="key">
                {{ label }}
              </label>
            </div>
          </div>

        </div>
      </div>

      <div class="flex gap-4 items-center w-full">
        <div class="flex w-1/3">
          <ColorField
            v-model="colors.background"
            name="backgroundColor"
            label="Background Color"
            float-label
          />
        </div>
        <div class="flex w-1/3">
          <ColorField
            v-model="colors.text"
            name="textColor"
            label="Text Color"
            float-label
          />
        </div>
        <div class="flex w-1/3">
          <ColorField
            v-model="colors.border"
            name="borderColor"
            label="Border Color"
            float-label
          />
        </div>
        <div class="flex w-1/3">
          <ColorField
            v-model="colors.hoverBorder"
            name="hoverBorderColor"
            label="Hover Border Color"
            float-label
          />
        </div>
      </div>
    </div>

    <div class="flex flex-row relative" v-else>
      <div 
        v-for="(slot, index) in slots"
        :key="'slot'+index"
        type="info" 
        class="p-2 h-fit truncate text-ellipsis rounded border-2 border-r-0 border-transparent bg-purple-950 cursor-pointer absolute inset-0 top-[--top] z-10 transition-all ease-in-out"
        :class="{ 
          'border-white w-[200.5px] rounded-r-none': activeSlot === index,
          'w-[180px]': activeSlot !== index,
        }"
        :style="{
          '--top': (index * 4) + 'rem'
        }"
        :data-id="index"
        @click="loadSlot(index)"
      >
        {{ slot.name || 'Slot ' + (index + 1) }}
      </div>

      <div class="flex flex-col w-full gap-2 p-2 rounded rounded-tl-none bg-purple-950 border-white border-2 ml-[199px] z-[9]">
        <TextField
          v-model="saveForm.name"
          name="name"
          label="Slot Name"
          float-label
        />
        <TextAreaField
          v-model="saveForm.settings"
          name="settings"
          label="Settings"
          float-label
          rows="5"
        />
        <div class="flex justify-end" v-if="saveForm.settings !== this.slots[activeSlot]?.settings">
          <Btn type="success" @click="restoreSlot">Restore Settings</Btn>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';

export default {
  name: 'Options',

  data() {
    return {
      dexOptions: {
        'gen1': 'RBY',
        'gen2': 'GSC',
        'gen3': 'RSE',
        'gen4': 'DPPT',
        'gen5': 'BW2',
        'gen6': 'XY',
        'gen7': 'SuMo',
        'gen8': 'SwSH',
        'gen9': 'SV',
      },

      sortOptions: {
        'random': 'Random',
        'byId': 'By National Dex',
        'byName': 'By Name',
      },

      selectionOptions: {
        'single': 'Single',
        'tetris': 'Tetris',
      },

      gridColorOptions: {
        'no': 'No',
        'tetris': 'Use Tetrimino Colors',
      },

      showSettings: false,
      availableSlots: 5,
      slots: [],
      activeSlot: 0,
      saveForm: {
        name: '',
        settings: '',
      },
    };
  },

  created() {
    this.activeSlot = parseInt(localStorage.getItem('settings_activeSlot')) || 0;
    this.populateSlots();
    this.loadSlot(this.activeSlot);

    this.$eventBus.$on('tetris/saveBoard', () => {
      this.saveSlot();
    });
  },

  methods: {
    randomizeSeed() {
      this.seed = Math.floor(Math.random() * 1000000);
    },

    populateSlots() {
      console.group('Populate Slots');
      for (let i = 0; i < this.availableSlots; i++) {
        console.group('Populating slot', i);
        let defaultSlot = { name: 'Slot ' + (i + 1), settings: {} };
        let lsSettings = localStorage.getItem('settings' + i);

        // if the slot is empty, we create a default one
        if (lsSettings === null || lsSettings.trim() === '') {
          console.log('No settings found for slot', i, 'creating default');
          this.slots[i] = defaultSlot;
          lsSettings = btoa(JSON.stringify(this.slots[i]));
          localStorage.setItem(
            'settings' + i,
            lsSettings
          );
          console.groupEnd();
          continue;
        }

        const slot = JSON.parse(atob(lsSettings));
        console.log('slotDef', i, ':', slot);
        if (!slot || typeof slot !== 'object') {
          console.warn('Invalid slot data for slot', i, 'creating default');
          this.slots[i] = defaultSlot;
          continue;
        }
        console.log('setting slot', i, 'to', slot);
        this.slots[i] = slot ;
        console.groupEnd();
      }
      console.groupEnd();
    },

    loadSlot(index) {
      this.activeSlot = index;
      const slot = this.slots[index];

      this.saveForm.name = slot.name || '';
      this.saveForm.settings = JSON.stringify(slot.settings) || '{}';
      this.$store.dispatch('tetris/setSettings', slot.settings);
      this.$eventBus.$emit('tetris/loadSlot');
      console.log('[loadSlot] Loaded slot', index, 'with settings:', slot.settings);
    },

    saveSlot() {
      let name = null;
      if (this.saveForm.name.trim() === '') {
        name = prompt('Please enter a name for the slot.');
      }
      const slot = {
        name: name !== null ? name : this.saveForm.name,
        settings: JSON.parse(this.settingsStr),
      };

      this.slots[this.activeSlot].name = slot.name;
      this.slots[this.activeSlot].settings = slot.settings;

      localStorage.setItem(
        'settings' + this.activeSlot,
        btoa(JSON.stringify(slot))
      );
      this.populateSlots();
    },

    restoreSlot() {
      if (!confirm('This will overwrite this slot, are you sure?')) {
        return;
      }


    }
  },

  computed: {
    ...mapFields('tetris', [
      'autosaveToLocalStorage',
      'tetriminosToGenerate',
      'seed',
      'pokedex',
      'perRow',
      'sort',
      'selectionType',
      'cellSize',
      'colors',
      'colorGrid',
    ]),
    ...mapGetters('tetris', ['settingsStr']),
  },
};
</script>