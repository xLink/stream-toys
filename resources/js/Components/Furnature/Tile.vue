<template>
  <div
    class="flex flex-col h-20 w-48 justify-end bg-zinc-400 rounded-lg shadow-lg px-4 py-2 relative overflow-hidden"
    :class="{
      'skeleton-box opacity-60': loading,
      'transition-all ease-in-out hover:opacity-80 cursor-pointer': !loading
    }"
    :style="'background-color: '+color+';'"
  >
    <span
      v-if="background.length > 0"
      class="z-10 text-5xl uppercase font-extrabold absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none select-none"
      style="color: white;mix-blend-mode: overlay;opacity: 20%;"
      v-text="background"
    />
    <slot name="background" />
    <span
      class="z-20 text-white text-sm font-bold"
      v-text="label"
    />
  </div>
</template>

<script>
export default {
  name: 'Tile',
  props: {
    label: {
      type: String,
      required: false,
      default: '',
    },
    background: {
      type: String,
      required: false,
      default: '',
    },
    color: {
      type: String,
      required: false,
      default: '#52525b',
    },
    loading: {
      type: Boolean,
      default: () => false,
    },
  },
};
</script>
