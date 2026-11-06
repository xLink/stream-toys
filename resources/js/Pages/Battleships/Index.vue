<template>
  <layout>
    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex flex-col">
        <h1 class="text-4xl font-bold text-center">Pokémon Battleships</h1>
        <h5 class="text-lg text-center">Create a new room</h5>
      </div>
    </div>

    <section class="flex flex-col gap-2 mt-6 items-center justify-center">
      <a
        v-if="$attrs.auth.user === null"
        class="flex py-2 bg-indigo-500 hover:bg-indigo-700 cursor-pointer justify-center items-center gap-1 text-2xl rounded-md px-8"
        :href="route('auth.discord')"
      >
        <fa icon="fa-brands fa-discord" size="sm" class="text-white" />
        Login with Discord
      </a>

      <div v-else class="flex flex-col rounded-lg bg-slate-800 p-6 gap-4 w-96">
        <TextField
          :model-value="$attrs.auth.user?.username || ''"
          name="username"
          label="Username"
          :disabled="true"
          float-label
          class="w-full"
        />

        <TextField
          v-model="roomName"
          name="name"
          label="Room Name"
          float-label
          placeholder="Enter room name"
        />

        <div class="flex flex-col gap-2">
          <label class="text-lg">Select Pokédex(es):</label>
          <div class="flex gap-4 flex-wrap">
            <div v-for="(label, key) in optionsObjects.pokedexes" :key="'dex'+key" class="flex items-center">
              <label :for="'dexc'+key">
                <input type="checkbox" :id="'dexc'+key" v-model="selectedPokedex" :value="key">
                {{ label }}
              </label>
            </div>
          </div>
        </div>

        <RangeField
          v-model="perRow"
          name="perRow"
          label="Pokémon Per Row"
          float-label
          :min="1"
          :max="40"
        />

        <div class="flex gap-2 items-end">
          <TextField
            v-model="seed"
            name="seed"
            label="Seed"
            float-label
            placeholder="Enter seed"
            class="flex-1"
          />
          <Btn type="warning" class="bg-red-500 text-white h-11 p-2 rounded" @click="randomizeSeed">
            <RefreshIcon />
          </Btn>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-lg">Ship Counts:</label>
          <div class="flex gap-4 flex-wrap">
            <div v-for="(def, key) in optionsObjects.shipDefs" :key="'ship'+key" class="flex items-center gap-2">
              <label class="text-sm w-24">{{ def.label }} ({{ def.size }})</label>
              <input
                type="number"
                v-model.number="shipCounts[key]"
                min="0"
                max="10"
                class="w-16 text-white text-sm p-1.5 rounded bg-slate-700 focus:outline-none focus:bg-slate-600"
              />
            </div>
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
            />
            <div class="flex items-end ml-2 gap-1">
              <label class="flex flex-col items-center text-xs">
                Custom
                <input type="color" v-model="playerColor" class="cursor-pointer rounded w-10 h-10" />
              </label>
            </div>
          </div>
        </div>

        <Btn type="success" @click="createRoom" :disabled="creating">
          {{ creating ? 'Creating...' : 'Create Room' }}
        </Btn>

        <p v-if="error" class="text-red-400 text-sm text-center">{{ error }}</p>
      </div>
    </section>
  </layout>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BattleshipsIndex',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      roomName: 'Battleships Room',
      selectedPokedex: ['kanto'],
      perRow: 10,
      seed: Math.floor(Math.random() * 1000000),
      playerColor: '#93c5fd',
      shipCounts: {
        carrier: 1,
        battleship: 1,
        cruiser: 1,
        submarine: 1,
        destroyer: 1,
      },
      creating: false,
      error: null,
    };
  },

  methods: {
    randomizeSeed() {
      this.seed = Math.floor(Math.random() * 1000000);
    },

    async createRoom() {
      this.creating = true;
      this.error = null;

      try {
        const response = await axios.post('/battleships', {
          name: this.roomName,
          pokedex: this.selectedPokedex,
          perRow: this.perRow,
          seed: this.seed,
          color: this.playerColor,
          shipCounts: this.shipCounts,
        });

        if (response.data['room-uuid']) {
          window.location.href = `/battleships/${response.data['room-uuid']}`;
        }
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to create room';
      } finally {
        this.creating = false;
      }
    },
  },
};
</script>
