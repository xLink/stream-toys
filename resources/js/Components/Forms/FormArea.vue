<template>
  <form
    class="text-sm bg-green-500 border-2 border-dark-green-500 rounded"
    @submit.prevent="emitSubmitted"
  >
    <div
      v-if="title && title.length"
      class="w-full text-white py-2 px-4 rounded-t"
      v-text="title"
    />
    <div class="flex flex-col space-y-6 bg-zinc-400 dark:bg-zinc-800 rounded-b">
      <slot />
    </div>
  </form>
</template>

<script>
export default {
  name: 'FormArea',
  props: {
    title: {
      type: [String, Boolean],
      required: true,
    },
  },
  emits: ['submitted'],

  methods: {
    emitSubmitted() {
      this.$emit('submitted');
    },
  },
};
</script>
