<template>
  <div class="flex flex-col bg-slate-800 p-4 rounded-full w-full pr-6">
    <div class="flex flex-row gap-2">
      <div class="flex text-transparent bg-clip-text font-extrabold bg-gradient-to-b from-red-300 to-red-800 text-4xl items-center">
        HP:
      </div>
      <div 
        class="flex flex-col bg-gray-300 rounded-full overflow-hidden h-20 w-full"
        :style="{ 
          '--width': current, 
          '--height': '20px',
          '--max': max, 
        }"
      >
        <div 
          class="flex h-full w-[--internalWidth] rounded-s-full transition-all duration-500 ease-in-out"
          :class="{
            [hpBarColor]: true,
            'rounded-e-full': current >= max,
          }"
          :style="{ 
            '--internalWidth': 'calc((var(--width) / var(--max)) * 100%)' 
          }"
        ></div>
      </div>
    </div>
    <div class="flex justify-center">
      <div class="flex font-extrabold text-white text-4xl items-center">
        {{ current }} / {{ max }}
      </div>      
    </div>
  </div>

</template>

<script>
export default {
  name: 'Gen3HP',

  props: {
    bar: {
      type: String,
      required: false,
      default: 'default',
    },
    max: {
      type: Number,
      required: true,
      default: 100,
    },
    current: {
      type: Number,
      required: true,
      default: 100,
    },
  },

  data() {
    return {
    }
  },

  mounted() {
  },

  methods: {
  },

  computed: {
    currentPercent() {
      return (this.current / this.max) * 100;
    },
    hpBarColor() {
      switch (true) {
        case this.currentPercent >= 66:
          return 'bg-green-500';
        case this.currentPercent >= 33:
          return 'bg-yellow-500';
        case this.currentPercent < 33:
          return 'bg-red-500';
        default:
          return 'bg-gray-500';
      }
    },
  },
};
</script>