<template>
  <layout>
    <div class="flex items-center justify-center min-h-screen">
      <div class="bg-slate-800 rounded-lg p-8 max-w-md w-full text-center">
        <div class="text-6xl mb-4">✓</div>
        <h1 class="text-3xl font-bold mb-2">Login Successful</h1>
        <p class="text-gray-400 mb-6">
          Welcome back, <span class="text-white font-semibold">{{ $attrs.auth?.user?.username }}</span>
        </p>

        <div class="flex flex-col gap-3">
          <a
            v-if="intended && intended !== '/'"
            :href="intended"
            class="block w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
          >
            Continue where you left off
          </a>
          <a
            href="/tetris-mp"
            class="block w-full py-3 px-4 bg-slate-600 hover:bg-slate-500 text-white rounded-lg font-medium transition-colors"
          >
            Tetris MP
          </a>
          <a
            href="/battleships"
            class="block w-full py-3 px-4 bg-slate-600 hover:bg-slate-500 text-white rounded-lg font-medium transition-colors"
          >
            Battleships
          </a>
          <a
            href="/tetris"
            class="block w-full py-3 px-4 bg-slate-600 hover:bg-slate-500 text-white rounded-lg font-medium transition-colors"
          >
            Single Player Tetris
          </a>
        </div>

        <p class="text-gray-500 text-sm mt-4" v-if="countdown > 0">
          Redirecting in {{ countdown }}s...
        </p>
      </div>
    </div>
  </layout>
</template>

<script>
export default {
  name: 'LoginSuccess',

  props: {
    intended: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      countdown: this.intended && this.intended !== '/' ? 3 : 0,
      timer: null,
    };
  },

  mounted() {
    if (this.countdown > 0) {
      this.timer = setInterval(() => {
        this.countdown--;
        if (this.countdown <= 0) {
          clearInterval(this.timer);
          window.location.href = this.intended;
        }
      }, 1000);
    }
  },

  unmounted() {
    if (this.timer) clearInterval(this.timer);
  },
};
</script>
