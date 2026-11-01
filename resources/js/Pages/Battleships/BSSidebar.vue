<template>
  <div class="flex flex-col gap-4 p-1">
    <!-- Players -->
    <div class="flex flex-col">
      <h2 class="text-2xl font-bold text-center">
        Players ({{ Object.values(players).length }})
      </h2>
      <ul class="flex flex-col items-center gap-1 mt-4">
        <li
          v-for="user in players"
          :key="user.username"
          class="flex gap-x-2 px-4 py-2 w-full bg-slate-800 rounded-lg items-center"
        >
          <div
            class="min-w-4 h-4 rounded-lg"
            :style="{ backgroundColor: user.color }"
          ></div>
          <span>{{ user.username }}</span>
          <span v-if="user.owner" class="ml-auto text-xs text-yellow-400">👑</span>
          <span v-if="phase === 'setup' && readyPlayers[user.username]" class="ml-auto text-green-400">✓</span>
          <span v-if="phase === 'battle' && user.color === turnColor" class="ml-auto text-yellow-400 animate-pulse">⚔️</span>
          <button
            v-if="phase === 'lobby' && isOwner && !user.owner && !readOnly"
            class="ml-auto text-xs text-red-400 hover:text-red-300 px-1"
            @click="kickPlayer(user.username)"
            title="Kick player"
          >✕</button>
        </li>
      </ul>
    </div>

    <!-- Lobby phase -->
    <div v-if="phase === 'lobby' && !readOnly" class="flex flex-col gap-2 p-2 bg-slate-800 rounded-lg">
      <p class="text-center text-slate-400 text-sm">
        {{ isOwner ? 'Configure room settings, then start the game.' : 'Waiting for the owner to start...' }}
      </p>
      <Btn v-if="isOwner" type="success" @click="startGame" :disabled="!canStart">
        Start Game
      </Btn>
      <p v-if="isOwner && !canStart" class="text-xs text-red-400 text-center">
        Need at least 2 different team colors
      </p>
      <Btn v-if="!isOwner" type="danger" @click="leaveRoom">
        Leave Room
      </Btn>
    </div>

    <!-- Setup phase -->
    <div v-if="phase === 'setup' && !readOnly" class="flex flex-col gap-2">
      <div class="bg-slate-800 rounded-lg p-2">
        <h3 class="text-lg font-bold text-center mb-2">Your Ships</h3>
        <ul class="flex flex-col gap-1">
          <li
            v-for="(def, key) in expandedShipDefs"
            :key="key"
            class="flex items-center gap-2 px-2 py-1 rounded"
            :class="isShipPlaced(key) ? 'bg-green-900/50' : 'bg-slate-700'"
          >
            <span class="flex gap-0.5">
              <span v-for="i in def.size" :key="i" class="w-2 h-2 rounded-sm" :class="isShipPlaced(key) ? 'bg-green-500' : 'bg-slate-500'"></span>
            </span>
            <span class="text-sm">{{ def.label }}</span>
            <span v-if="isShipPlaced(key)" class="ml-auto text-green-400 text-xs">Placed</span>
          </li>
        </ul>
      </div>

      <Btn
        :type="readyPlayers[currentPlayer] ? 'warning' : 'success'"
        @click="toggleReady"
        :disabled="!allShipsPlaced"
      >
        {{ readyPlayers[currentPlayer] ? 'Unready' : 'Ready!' }}
      </Btn>
      <p v-if="!allShipsPlaced" class="text-xs text-slate-400 text-center">
        Place all ships to ready up
      </p>
    </div>

    <!-- Battle phase -->
    <div v-if="phase === 'battle'" class="flex flex-col gap-2">
      <div class="bg-slate-800 rounded-lg p-2">
        <h3 class="text-lg font-bold text-center mb-2">Turn Order</h3>
        <div class="flex flex-col gap-1">
          <div
            v-for="color in teamColors"
            :key="color"
            class="flex items-center gap-2 px-2 py-1 rounded"
            :class="color === turnColor ? 'bg-yellow-900/50 ring-1 ring-yellow-500' : 'bg-slate-700'"
          >
            <div class="w-4 h-4 rounded" :style="{ backgroundColor: color }"></div>
            <span class="text-sm">{{ color === turnColor ? 'Active' : 'Waiting' }}</span>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 rounded-lg p-2">
        <h3 class="text-lg font-bold text-center mb-2">Your Ships</h3>
        <ul class="flex flex-col gap-1">
          <li
            v-for="(def, key) in expandedShipDefs"
            :key="key"
            class="flex items-center gap-2 px-2 py-1 rounded"
            :class="isShipSunk(key) ? 'bg-red-900/50' : 'bg-slate-700'"
          >
            <span class="flex gap-0.5">
              <span v-for="i in def.size" :key="i" class="w-2 h-2 rounded-sm" :class="isShipSunk(key) ? 'bg-red-500' : 'bg-green-500'"></span>
            </span>
            <span class="text-sm" :class="isShipSunk(key) ? 'line-through text-red-400' : ''">{{ def.label }}</span>
            <span v-if="isShipSunk(key)" class="ml-auto text-red-400 text-xs">Sunk!</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Finished phase -->
    <div v-if="phase === 'finished'" class="flex flex-col gap-2 p-2 bg-slate-800 rounded-lg">
      <h3 class="text-2xl font-bold text-center">Game Over!</h3>
      <div class="flex items-center justify-center gap-2 mt-2">
        <div class="w-6 h-6 rounded" :style="{ backgroundColor: winner }"></div>
        <span class="text-lg font-bold">Winner!</span>
      </div>
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';
import axios from 'axios';

export default {
  name: 'BSSidebar',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
    readOnly: {
      type: Boolean,
      default: false,
    },
  },

  methods: {
    async startGame() {
      try {
        await axios.post(`/battleships/${this.uuid}/start-game`);
        this.$store.dispatch('battleships/setPhase', { phase: 'setup' });
      } catch (e) {
        alert(e.response?.data?.message || 'Failed to start game');
      }
    },

    async toggleReady() {
      this.$store.dispatch('battleships/toggleReady');
    },

    isShipPlaced(key) {
      return this.myShips.some(s => s.shipType === key);
    },

    async leaveRoom() {
      try {
        await axios.post(`/battleships/${this.uuid}/leave`);
        window.location.reload();
      } catch (e) {
        alert(e.response?.data?.message || 'Failed to leave room');
      }
    },

    async kickPlayer(username) {
      try {
        const response = await axios.post(`/battleships/${this.uuid}/kick`, { username });
        if (response.data.players) {
          this.$store.dispatch('battleships/setPlayers', response.data.players);
        }
      } catch (e) {
        alert(e.response?.data?.message || 'Failed to kick player');
      }
    },

    isShipSunk(key) {
      const ship = this.myShips.find(s => s.shipType === key);
      if (!ship) return false;
      return ship.cells.every(c =>
        this.shots.some(s => s.x === c.x && s.y === c.y && s.hit)
      );
    },
  },

  computed: {
    ...mapGetters('battleships', [
      'isOwner',
      'isMyTurn',
      'allShipsPlaced',
      'teamColors',
      'expandedShipDefs',
    ]),
    ...mapFields('battleships', [
      'uuid',
      'phase',
      'turnColor',
      'winner',
      'players',
      'currentPlayer',
      'playerColor',
      'myShips',
      'shots',
      'shipDefs',
      'readyPlayers',
    ]),

    canStart() {
      const uniqueColors = [...new Set(Object.values(this.players).map(p => p.color))];
      return uniqueColors.length >= 2;
    },
  },
};
</script>
