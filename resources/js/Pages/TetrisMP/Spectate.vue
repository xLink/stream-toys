<template>
  <layout>
    <div class="absolute top-4 right-4 z-50 flex items-center gap-3">
      <span
        class="font-bold text-sm px-2 py-1 rounded select-none"
        :class="wsConnected ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        :title="'WebSocket: ' + wsState"
      >
        WS
      </span>
      <span class="font-bold text-sm px-2 py-1 rounded select-none bg-purple-600 text-white">
        SPECTATOR
      </span>
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex flex-col">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
        <h5 class="text-lg text-center">{{ mode }} - {{ name }} (Spectating)</h5>
      </div>
    </div>

    <section class="flex overflow-hidden" style="height: calc(100vh - 5rem);">
      <div class="flex flex-col flex-1 min-h-0">
        <MPBoard :board-popout="false" :is-read-only="true" />
      </div>
      <div class="relative bg-slate-700 w-2/12 overflow-y-auto">
        <MPSidebar />
      </div>
    </section>
  </layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import MPSidebar from '@/Pages/TetrisMP/MPSidebar.vue';
import MPBoard from '@/Pages/TetrisMP/MPBoard.vue';

export default {
  name: 'Spectate',
  components: { MPSidebar, MPBoard },

  props: {
    pokedexData: { type: Object, required: true },
    optionsObjects: { type: Object, required: true },
    uuid: { type: String, required: true },
    players: { type: Array, default: () => [] },
    state: { type: Object, default: () => ({}) },
  },

  data() {
    return {
      wsState: 'disconnected',
    };
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

  beforeMount() {
    this.$store.dispatch('tetrismp/setUuid', this.uuid);
    this.$store.dispatch('tetrismp/setState', this.state);
    this.$store.dispatch('tetrismp/setPlayers', this.players);
    this.$store.dispatch('tetrismp/setPieceDef', this.optionsObjects.defaultTetriminos);
    this.$store.dispatch('tetrismp/regeneratePieces');
    this.$store.dispatch('tetrismp/setReadOnly', true);

    this.joinSpectatorChannel();
  },

  unmounted() {
    if (this._channel) {
      window.Echo.leave(this._channel);
    }
  },

  methods: {
    joinSpectatorChannel() {
      this._channel = ['App', 'TetrisMPSpectator', this.uuid].join('.');
      window.Echo.channel(this._channel)
        .listen('Tetris\\UpdateBoard', (event) => {
          this.$store.dispatch('tetrismp/setState', event.objRoom.state);
          this.$store.dispatch('tetrismp/setPlayers', event.objRoom.players);
          this.$store.dispatch('tetrismp/regeneratePieces');
        })
        .listen('Tetris\\ClearBoard', () => {
          this.$store.dispatch('tetrismp/clearBoard');
        })
        .listen('Tetris\\UpdateHistory', (event) => {
          this.$store.dispatch('tetrismp/setHistory', event.history);
        })
        .listen('Tetris\\UpdateUsers', (event) => {
          this.$store.dispatch('tetrismp/setPlayers', event.users);
        })
        .listen('Tetris\\NewCell', (event) => {
          this.$store.dispatch('tetrismp/addSelectedCell', { ...event.cell, ignoreChecks: true });
          this.$store.dispatch('tetrismp/addHistory', event.cell);
        })
      ;
    },
  },

  computed: {
    ...mapFields('tetrismp', ['mode', 'name']),
    wsConnected() {
      return this.wsState === 'connected';
    },
  },
};
</script>
