<template>
  <div
    class="flex flex-col h-full border border-zinc-300 dark:border-zinc-500 glass text-black dark:text-white rounded gap-2"
  >
    <div class="absolute top-0 right-0 mr-2 mt-2 z-10">
      <div
        class="p-1 text-zinc-400 dark:text-zinc-800 hover:text-zinc-500 cursor-pointer"
        :title="Close"
        @click="close_modal()"
      >
        x
      </div>
    </div>
    <div class="flex flex-col p-2">
      <Header3 text="User Details" class="!text-black dark:!text-white" />
      <p class="text-black dark:text-white">
        Identify Yourself!
      </p>
    </div>
    <div class="flex flex-col space-y-4 p-2">
      Username: 

      Color: 
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserDetails',
  emits: ['close'],

  methods: {
    close_modal() {
      this.$emit('close');
    },
  },
};
</script>
