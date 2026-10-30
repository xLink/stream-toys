<template>
  <div class="flex flex-col gap-4 bg-black/30 rounded-lg p-4 w-full">
    <h2 class="text-xl font-bold">Archipelago Connection</h2>

    <div class="flex flex-col gap-3 max-w-md">
      <TextField
        v-model="hostname"
        name="ap_hostname"
        label="Hostname"
        :float-label="true"
        :disabled="isConnected"
      />
      <NumberField
        v-model="port"
        name="ap_port"
        label="Port"
        :float-label="true"
        :min="1"
        :max="65535"
        :disabled="isConnected"
      />
      <TextField
        v-model="slotName"
        name="ap_slot"
        label="Slot Name"
        :float-label="true"
        :disabled="isConnected"
      />
      <TextField
        v-model="password"
        name="ap_password"
        label="Password"
        :float-label="true"
        type="password"
        :disabled="isConnected"
      />

      <div class="flex items-center gap-3">
        <Btn
          v-if="!isConnected"
          type="info"
          class="!bg-green-600 hover:!bg-green-700"
          :disabled="!canConnect"
          @click="$emit('connect')"
        >
          Connect
        </Btn>
        <Btn
          v-else
          type="info"
          class="!bg-red-600 hover:!bg-red-700"
          @click="$emit('disconnect')"
        >
          Disconnect
        </Btn>

        <span
          class="inline-flex items-center gap-1.5 text-sm"
          :class="{
            'text-gray-400': status === 'disconnected',
            'text-yellow-400': status === 'connecting',
            'text-green-400': status === 'connected',
            'text-red-400': status === 'error',
          }"
        >
          <span
            class="inline-block w-2 h-2 rounded-full"
            :class="{
              'bg-gray-400': status === 'disconnected',
              'bg-yellow-400 animate-pulse': status === 'connecting',
              'bg-green-400': status === 'connected',
              'bg-red-400': status === 'error',
            }"
          />
          {{ statusLabel }}
        </span>
      </div>

      <p v-if="lastError" class="text-red-400 text-sm">
        {{ lastError }}
      </p>
    </div>

    <div class="flex flex-col gap-1">
      <div class="flex items-center justify-between">
        <label class="text-sm font-bold opacity-75">Message Log</label>
        <button
          v-if="log.length"
          class="text-xs opacity-50 hover:opacity-100"
          @click="$store.dispatch('archipelago/clearLog')"
        >
          Clear
        </button>
      </div>
      <textarea
        ref="logArea"
        readonly
        class="w-full h-48 bg-black/50 text-green-300 text-xs font-mono p-2 rounded border border-zinc-600 resize-y"
        :value="logText"
      />
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'ArchipelagoPanel',
  emits: ['connect', 'disconnect'],

  computed: {
    ...mapFields('archipelago', [
      'hostname',
      'port',
      'slotName',
      'password',
      'status',
      'lastError',
      'log',
    ]),

    isConnected() {
      return this.status === 'connected';
    },
    canConnect() {
      return this.hostname.trim() !== ''
        && this.slotName.trim() !== ''
        && this.status !== 'connecting';
    },
    statusLabel() {
      const labels = {
        disconnected: 'Disconnected',
        connecting: 'Connecting...',
        connected: 'Connected',
        error: 'Error',
      };
      return labels[this.status] || this.status;
    },
    logText() {
      return this.log.join('\n');
    },
  },

  watch: {
    logText() {
      this.$nextTick(() => {
        const el = this.$refs.logArea;
        if (el) el.scrollTop = el.scrollHeight;
      });
    },
  },
};
</script>
