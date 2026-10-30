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
        <h5 class="text-lg text-center">
          {{ mode }} - {{ name }} (Spectating{{ viewingPlayer ? ' ' + viewingPlayer : '' }})
        </h5>
      </div>
    </div>

    <section class="flex overflow-hidden" style="height: calc(100vh - 5rem);">
      <Teleport to="body" :disabled="!boardPopout">
        <div
          :class="boardPopout
            ? 'fixed inset-0 z-50 bg-slate-900 flex flex-col'
            : 'flex flex-col flex-1 min-h-0'"
        >
          <MPBoard :board-popout="boardPopout" @toggle-popout="boardPopout = !boardPopout" />
        </div>
      </Teleport>
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
      boardPopout: false,
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
    this.$store.dispatch('tetrismp/setPieceDef', this.$store.state.app.defaultTetriminos);
    this.$store.dispatch('tetrismp/setUuid', this.uuid);
    this.$store.dispatch('tetrismp/setPlayers', this.players);
    this.$store.dispatch('tetrismp/setState', this.state);
    this.$store.dispatch('tetrismp/setBoard', {
      board: Object.values(this.pokedexData),
      perRow: this.state.perRow,
    });
    this.$store.dispatch('tetrismp/regeneratePieces');
    this.$store.dispatch('tetrismp/setReadOnly', true);

    this.pickInitialPlayer();

    // Re-replay history after player is set to ensure step count is correct
    this.$store.dispatch('tetrismp/setState', this.state);
    this.$store.dispatch('tetrismp/setBoard', {
      board: Object.values(this.pokedexData),
      perRow: this.state.perRow,
    });
    this.$store.dispatch('tetrismp/regeneratePieces');

    this.joinSpectatorChannel();
  },

  unmounted() {
    if (this._channel) {
      window.Echo.leave(this._channel);
    }
  },

  methods: {
    pickInitialPlayer() {
      const urlPlayer = new URLSearchParams(window.location.search).get('player');
      let pick = null;

      if (urlPlayer) {
        const match = this.players.find(p => p.username.toLowerCase() === urlPlayer.toLowerCase());
        if (match) pick = match.username;
      }

      if (!pick && this.players.length >= 1) {
        pick = this.players[0].username;
      }

      if (pick) {
        this.$store.dispatch('tetrismp/setViewingPlayer', pick);
        this.$store.dispatch('tetrismp/setCurrentPlayer', pick);
      }
    },

    joinSpectatorChannel() {
      this._channel = ['App', 'TetrisMPSpectator', this.uuid].join('.');
      window.Echo.channel(this._channel)
        .listen('Tetris\\UpdateBoard', (event) => {
          const oldPokedex = JSON.stringify(this.$store.state.tetrismp.settings.pokedex);
          this.$store.dispatch('tetrismp/setState', event.objRoom.state);
          this.$store.dispatch('tetrismp/setPlayers', event.objRoom.players);
          this.$store.dispatch('tetrismp/regeneratePieces');
          if (JSON.stringify(event.objRoom.state.pokedex) !== oldPokedex) {
            this.$inertia.reload({ only: ['pokedexData'] });
          }
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
          const currentPlayer = this.$store.state.tetrismp.currentPlayer;
          if (event.cell.username?.toLowerCase() === currentPlayer?.toLowerCase()) {
            this.$store.dispatch('tetrismp/increaseStep');
          }
          this.$store.dispatch('tetrismp/regeneratePieces');
        })
        .listen('Tetris\\UpdateTrackedCells', (event) => {
          this.$store.commit('tetrismp/updateField', { path: 'trackedCells', value: event.trackedCells });
        })
      ;
    },
  },

  computed: {
    ...mapFields('tetrismp', ['mode', 'name', 'viewingPlayer']),
    wsConnected() {
      return this.wsState === 'connected';
    },
  },
};
</script>
