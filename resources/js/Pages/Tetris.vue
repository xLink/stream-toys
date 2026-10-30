<template>
  <Layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50 flex items-center gap-3">
      <span
        class="font-bold text-sm px-2 py-1 rounded select-none"
        :class="wsConnected ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        :title="'WebSocket: ' + wsState"
      >
        WS
      </span>
      <span
        class="font-bold text-sm px-2 py-1 rounded select-none"
        :class="apConnected ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        @click="showAPPanel = !showAPPanel"
      >
        AP
      </span>
      <MenuOpenIcon v-if="showOptions" class="text-white" @click="showOptions = !showOptions" />
      <MenuCloseIcon v-if="!showOptions" class="text-white" @click="showOptions = !showOptions" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
      </div>
    </div>

    <div class="flex flex-col w-full p-4 gap-2">
      <ArchipelagoPanel
        v-show="showAPPanel"
        @connect="$store.dispatch('archipelago/connect')"
        @disconnect="$store.dispatch('archipelago/disconnect')"
      />
      <Options v-show="showOptions" />
      <Board :pokedexData="pokedexData" :inputDisabled="showOptions || showAPPanel" :lastSeenPokemon="apLastSeen" :apSeen="apSeen" :apCaught="apCaught" />
    </div>
  </Layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';
import Board from './Tetris/Board.vue';
import Options from './Tetris/Options.vue';
import ArchipelagoPanel from './Tetris/ArchipelagoPanel.vue';

export default {
  name: 'TetrisIndex',
  components: { Board, Options, ArchipelagoPanel },

  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      showOptions: false,
      showAPPanel: false,
      username: '',
      wsState: 'disconnected',
    }
  },

  mounted() {
    if (window.Echo?.connector?.pusher) {
      const pusher = window.Echo.connector.pusher;
      this.wsState = pusher.connection?.state || 'disconnected';
      pusher.connection.bind('state_change', ({ current }) => {
        this.wsState = current;
      });
    }
  },

  created() {
    let localData = localStorage.getItem('tetris2_save');

    let loaded = false;
    if (localData?.trim() !== '') {
      try {
        this.$store.dispatch('tetris2/loadBoard');
        loaded = true;
      } catch (e) {
      }
    }

    if (!loaded) {
      if (this.seed === 0) {
        this.$store.dispatch('tetris2/setSeed', Math.floor(Math.random() * 1000000));
      }
      if (this.rng === null) {
        this.$store.dispatch('tetris2/setRNG');
      }
      if (this.defaultTetriminos && Object.keys(this.pieceDef).length === 0) {
        this.$store.dispatch('tetris2/setPieceDef', this.defaultTetriminos);
      }
    }
  },

  beforeUnmount() {
    this.$store.dispatch('archipelago/cleanup');
  },

  computed: {
    ...mapFields('app', ['defaultTetriminos']),
    ...mapFields('tetris2', [
      'seed',
      'rng',
      'pieceDef',
    ]),
    ...mapFields('archipelago', { apLastSeen: 'lastSeen', apSeen: 'seen', apCaught: 'caught' }),
    ...mapGetters('archipelago', { apConnected: 'isConnected' }),
    wsConnected() {
      return this.wsState === 'connected';
    },
  },
};
</script>
