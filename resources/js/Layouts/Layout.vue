<template>
  <div id="layout" class="w-full min-h-screen bg-gray-900 text-white">
    <main>
      <slot />
    </main>
  </div>
</template>

<script>
export default {
  name: 'Layout',
};
</script>