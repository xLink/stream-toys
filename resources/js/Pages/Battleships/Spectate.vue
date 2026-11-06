<template>
  <layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50 flex items-center gap-3">
      <span
        class="font-bold text-sm px-2 py-1 rounded select-none"
        :class="wsConnected ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        :title="'WebSocket: ' + wsState"
      >
        WS
      </span>
      <MenuOpenIcon v-if="showSidebar" class="text-white" @click="toggleSidebar" />
      <MenuCloseIcon v-if="!showSidebar" class="text-white" @click="toggleSidebar" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex flex-col">
        <h1 class="text-4xl font-bold text-center">Pokémon Battleships</h1>
        <h5 class="text-lg text-center">
          {{ name }}
          <span class="text-sm ml-2 px-2 py-0.5 rounded bg-purple-600">Spectating</span>
          <span class="text-sm ml-1 px-2 py-0.5 rounded" :class="phaseClass">{{ phaseLabel }}</span>
        </h5>
      </div>
    </div>

    <div class="flex w-full h-8">
      <div
        v-for="(p, i) in phases"
        :key="p.key"
        class="relative flex items-center justify-center flex-1 text-xs font-semibold tracking-wide transition-all"
        :class="phase === p.key
          ? p.activeClass
          : phaseIndex > i
            ? 'bg-slate-600 text-slate-300'
            : 'bg-slate-800 text-slate-500'"
      >
        <span class="z-10">{{ p.label }}</span>
        <svg v-if="i < phases.length - 1" class="absolute right-0 top-0 h-full w-4 z-20 -mr-2" viewBox="0 0 16 32" preserveAspectRatio="none">
          <polygon
            points="0,0 16,16 0,32"
            :class="phase === p.key
              ? p.svgFill
              : phaseIndex > i
                ? 'fill-slate-600'
                : 'fill-slate-800'"
          />
        </svg>
      </div>
    </div>

    <section class="flex flex-row w-full overflow-hidden" style="height: calc(100vh - 5rem)">
      <div class="flex flex-col h-full overflow-hidden transition-all"
        :class="{ 'w-10/12': showSidebar, 'w-full': !showSidebar }"
      >
        <BSBoard :input-disabled="true" :remote-interactions="remoteInteractions" />
      </div>
      <div v-if="showSidebar" class="relative bg-slate-700 w-2/12 overflow-y-auto">
        <BSSidebar :options-objects="optionsObjects" :read-only="true" />
      </div>
    </section>

    <!-- Winner overlay -->
    <div v-if="phase === 'finished'" class="fixed inset-0 z-40 bg-black/60 flex items-center justify-center">
      <div class="bg-slate-800 rounded-xl p-8 text-center shadow-2xl">
        <h2 class="text-4xl font-bold mb-4">Game Over!</h2>
        <div class="flex items-center justify-center gap-3 mb-4">
          <div class="w-8 h-8 rounded" :style="{ backgroundColor: winner }"></div>
          <span class="text-2xl">Team wins!</span>
        </div>
      </div>
    </div>
  </layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import BSBoard from '@/Pages/Battleships/BSBoard.vue';
import BSSidebar from '@/Pages/Battleships/BSSidebar.vue';

export default {
  name: 'BattleshipsSpectate',
  components: { BSBoard, BSSidebar },

  props: {
    pokedexData: { type: Object, required: true },
    optionsObjects: { type: Object, required: true },
    uuid: { type: String, required: true },
    players: { type: [Object, Array], required: true },
    state: { type: Object, required: true },
  },

  data() {
    return {
      showSidebar: true,
      wsState: 'disconnected',
      onlineUsers: [],
      remoteInteractions: {},
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
    this.$store.dispatch('battleships/setShipDefs', this.optionsObjects.shipDefs);
    this.$store.dispatch('battleships/setUuid', this.uuid);
    this.$store.dispatch('battleships/setPlayers', this.players);
    this.$store.dispatch('battleships/setState', this.state);
    this.$store.dispatch('battleships/setBoard', {
      board: Object.values(this.pokedexData),
      perRow: this.state.perRow,
    });

    this.showSidebar = localStorage.getItem('bs-showSidebar') !== 'false';
    this.joinChannel();
  },

  unmounted() {
    if (this._channel) {
      window.Echo.leave(this._channel);
    }
  },

  methods: {
    joinChannel() {
      this._channel = ['App', 'Battleships', this.uuid].join('.');
      window.Echo.join(this._channel)
        .here((users) => { this.onlineUsers = users; })
        .joining((user) => { this.onlineUsers.push(user); })
        .leaving((user) => { this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id); })
        .listen('Battleships\\PhaseChanged', (event) => {
          this.$store.dispatch('battleships/setPhase', {
            phase: event.phase,
            turnColor: event.turnColor,
          });
        })
        .listen('Battleships\\PlayerReady', (event) => {
          this.$store.dispatch('battleships/setPlayerReady', {
            username: event.username,
            ready: event.ready,
          });
        })
        .listen('Battleships\\ShotFired', (event) => {
          this.$store.dispatch('battleships/addShot', {
            x: event.cell.x,
            y: event.cell.y,
            hit: event.hit,
            shooterColor: event.shooterColor,
            sunkShip: event.sunkShip,
            sunkShipCells: event.sunkShipCells,
          });
          this.$store.commit('battleships/updateField', { path: 'turnColor', value: event.turnColor });
        })
        .listen('Battleships\\ShipsPlaced', (event) => {
          // notification only
        })
        .listen('Battleships\\GameOver', (event) => {
          this.$store.commit('battleships/updateField', { path: 'phase', value: 'finished' });
          this.$store.commit('battleships/updateField', { path: 'winner', value: event.winnerColor });
        })
        .listen('Battleships\\SettingsUpdated', (event) => {
          const oldPokedex = JSON.stringify(this.$store.state.battleships.settings.pokedex);
          this.$store.dispatch('battleships/setState', event.state);
          this.$store.dispatch('battleships/setPlayers', event.players);
          if (JSON.stringify(event.state.pokedex) !== oldPokedex) {
            this.$inertia.reload({ only: ['pokedexData'] });
          } else {
            this.$store.dispatch('battleships/setBoard', {
              board: Object.values(this.pokedexData),
              perRow: event.state.perRow,
            });
          }
        })
        .listen('Battleships\\UpdateUsers', (event) => {
          this.$store.dispatch('battleships/setPlayers', event.users);
        })
        .listenForWhisper('interaction', (data) => {
          this.remoteInteractions = { ...this.remoteInteractions, [data.username]: data };
        })
        .listen('Battleships\\InteractionSync', (event) => {
          this.remoteInteractions = { ...this.remoteInteractions, [event.username]: event };
        })
      ;
    },

    toggleSidebar() {
      this.showSidebar = !this.showSidebar;
      localStorage.setItem('bs-showSidebar', this.showSidebar);
    },
  },

  computed: {
    ...mapFields('battleships', [
      'name',
      'phase',
      'winner',
    ]),

    wsConnected() {
      return this.wsState === 'connected';
    },

    phaseLabel() {
      const labels = { lobby: 'Lobby', setup: 'Place Ships', battle: 'Battle!', finished: 'Finished' };
      return labels[this.phase] || this.phase;
    },

    phaseClass() {
      const classes = {
        lobby: 'bg-blue-600',
        setup: 'bg-yellow-600',
        battle: 'bg-red-600',
        finished: 'bg-green-600',
      };
      return classes[this.phase] || 'bg-slate-600';
    },

    phases() {
      return [
        { key: 'lobby', label: 'Lobby', activeClass: 'bg-blue-600 text-white', svgFill: 'fill-blue-600' },
        { key: 'setup', label: 'Place Ships', activeClass: 'bg-yellow-600 text-white', svgFill: 'fill-yellow-600' },
        { key: 'battle', label: 'Battle', activeClass: 'bg-red-600 text-white', svgFill: 'fill-red-600' },
        { key: 'finished', label: 'Finished', activeClass: 'bg-green-600 text-white', svgFill: 'fill-green-600' },
      ];
    },

    phaseIndex() {
      return this.phases.findIndex(p => p.key === this.phase);
    },
  },

  watch: {
    '$store.state.battleships.seed'() {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(this.pokedexData),
        perRow: this.$store.state.battleships.settings.perRow,
      });
    },

    '$store.state.battleships.settings.perRow'(perRow) {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(this.pokedexData),
        perRow,
      });
    },

    '$store.state.battleships.shuffleBlanks'() {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(this.pokedexData),
        perRow: this.$store.state.battleships.settings.perRow,
      });
    },

    '$store.state.battleships.blankRows'() {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(this.pokedexData),
        perRow: this.$store.state.battleships.settings.perRow,
      });
    },

    '$store.state.battleships.settings.sort'() {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(this.pokedexData),
        perRow: this.$store.state.battleships.settings.perRow,
      });
    },

    pokedexData(newData) {
      this.$store.dispatch('battleships/setBoard', {
        board: Object.values(newData),
        perRow: this.$store.state.battleships.settings.perRow,
      });
    },
  },
};
</script>
