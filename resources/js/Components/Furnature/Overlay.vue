<template>
  <div
    class="flex flex-col border border-zinc-300 dark:border-zinc-500 dark:text-white text-white rounded-l"
    :class="[classes]"
  >
    <slot />
  </div>
</template>

<script>
export default {
  name: 'Overlay',
  props: {
    classes: {
      type: String,
      required: false,
      default: () => ' w-[80%] h-[80vh]',
    },
  },
};
</script>
