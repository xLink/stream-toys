<template>
  <div
    id="modal"
    class="flex flex-row z-[100] top-0 left-0 h-full w-full bg-black/40 transition-all absolute justify-center items-center empty:hidden empty:bg-black/0"
    @click.self="closeModal"
  />
  
  <div id="layout" class="w-full min-h-screen">
    <Teleport to="#modal">
      <Overlay v-if="modal === 'UserDetails'">
        <UserDetails @close="modal = null;" @open-mfa-activate="showUserDetails" />
      </Overlay>
    </Teleport>
    <main>
      <slot />
    </main>
  </div>
</template>

<script>
import UserDetails from '@/Pages/Misc/UserDetails.vue';

export default {
  name: 'Layout',
  components: {
    UserDetails,
  },

  data() {
    return {
      modal: null,
    };
  },

  beforeMount() {

    this.$eventBus.$on('modal-close', () => {
      this.modal = null;
    });

    this.$eventBus.$on('modal-open', (modal) => {
      this.modal = modal;
    });
  },

  unmounted() {
    this.$eventBus.$off('modal-close');
    this.$eventBus.$off('modal-open');
  },

};
</script>