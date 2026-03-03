<template>
  <slot name="header">
    <h1 class="text-2xl font-bold">{{ header }}</h1>
  </slot>
  <div :v-bind="$attrs" class="flex flex-row gap-2 flex-wrap mt-2 bg-chks-dark-gray-50 dark:bg-chks-dark-gray-900 rounded rounded-tl-none px-2 pb-2 pt-2">
    <slot />
  </div>
</template>

<script>
export default {
  name: 'ContentArea',
};
</script>